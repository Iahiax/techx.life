<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * وحدة التحكم في إدارة الحسابات البنكية للعميل
 * 
 * تتيح هذه الوحدة للعميل عرض حساباته البنكية المرتبطة،
 * وإضافة حسابات جديدة عبر Open Banking (Lean)،
 * وتحديد حساب الراتب والحساب الأساسي، وحذف الحسابات.
 */
class AccountController extends Controller
{
    /**
     * إنشاء مثيل جديد مع تطبيق middleware المصادقة
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            // التأكد من أن المستخدم من نوع customer
            if (Auth::user()->type !== 'customer') {
                abort(403, 'هذه الصفحة مخصصة للعملاء فقط.');
            }
            return $next($request);
        });
    }

    /**
     * عرض قائمة الحسابات البنكية للعميل
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $accounts = CustomerAccount::where('customer_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('is_salary_account', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('customer.accounts.index', compact('accounts'));
    }

    /**
     * عرض نموذج إضافة حساب جديد (عبر Lean)
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // هنا يمكن عرض تعليمات ربط الحساب عبر Lean
        // أو إعادة توجيه مباشرة إلى Lean Connect
        return view('customer.accounts.create');
    }

    /**
     * بدء عملية ربط حساب جديد عبر Lean
     * يقوم بإعادة التوجيه إلى بوابة Lean Connect
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect()
    {
        $user = Auth::user();

        // بناء رابط Lean Connect مع معرّف المستخدم الخاص بنا (user id أو national_id)
        // Lean سيعيد المستخدم إلى callback URL مع بيانات الحساب
        $leanConnectUrl = config('services.lean.connect_url');
        $clientId = config('services.lean.client_id');
        $redirectUri = route('customer.accounts.callback');

        $state = encrypt(json_encode([
            'user_id' => $user->id,
            'national_id' => $user->national_id,
        ]));

        $url = $leanConnectUrl . '?client_id=' . $clientId
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&state=' . $state
            . '&response_type=code'
            . '&scope=accounts,transfers';

        return redirect()->away($url);
    }

    /**
     * معالجة الرد القادم من Lean بعد ربط الحساب
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        // التحقق من وجود خطأ
        if ($request->has('error')) {
            Log::error('Lean callback error', ['error' => $request->get('error')]);
            return redirect()->route('customer.accounts.index')
                ->with('error', 'فشل ربط الحساب البنكي: ' . $request->get('error_description', 'خطأ غير معروف'));
        }

        // التحقق من state
        $state = $request->get('state');
        if (!$state) {
            return redirect()->route('customer.accounts.index')->with('error', 'طلب غير صالح.');
        }

        try {
            $stateData = json_decode(decrypt($state), true);
            $userId = $stateData['user_id'] ?? null;
            if (!$userId || $userId != Auth::id()) {
                return redirect()->route('customer.accounts.index')->with('error', 'بيانات غير متطابقة.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to decrypt state', ['error' => $e->getMessage()]);
            return redirect()->route('customer.accounts.index')->with('error', 'طلب غير صالح.');
        }

        // الحصول على الكود
        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('customer.accounts.index')->with('error', 'لم يتم استلام رمز التفويض.');
        }

        // تبادل الكود مع access token عبر Lean API
        // هذا الجزء يتطلب استخدام LeanService
        try {
            $leanService = app(\App\Services\OpenBanking\LeanService::class);
            $tokenData = $leanService->exchangeCode($code);
            // استخدام tokenData لاسترجاع قائمة الحسابات
            $accounts = $leanService->getAccounts($tokenData['access_token']);

            // تخزين الحسابات في قاعدة البيانات
            foreach ($accounts as $accData) {
                // التحقق من عدم تكرار الحساب (حسب provider_account_id)
                $existing = CustomerAccount::where('provider_account_id', $accData['id'])->first();
                if (!$existing) {
                    CustomerAccount::create([
                        'customer_id' => Auth::id(),
                        'provider_account_id' => $accData['id'],
                        'iban' => $accData['iban'] ?? null,
                        'bank_name' => $accData['bank']['name'] ?? 'غير معروف',
                        'account_name' => $accData['name'] ?? 'حساب بنكي',
                        'is_salary_account' => false,
                        'is_primary' => false,
                        'current_balance' => $accData['balance'] ?? 0,
                        'currency' => $accData['currency'] ?? 'SAR',
                    ]);
                }
            }

            return redirect()->route('customer.accounts.index')
                ->with('success', 'تم ربط الحسابات البنكية بنجاح.');

        } catch (\Exception $e) {
            Log::error('Lean exchange failed', ['error' => $e->getMessage()]);
            return redirect()->route('customer.accounts.index')
                ->with('error', 'حدث خطأ أثناء ربط الحسابات. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث إعدادات حساب معين (تعيين كحساب راتب أو أساسي)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $account = CustomerAccount::where('customer_id', Auth::id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'is_salary_account' => 'sometimes|boolean',
            'is_primary' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // إذا تم تعيين is_primary = true، يجب إزالة الخاصية عن باقي الحسابات
        if ($request->boolean('is_primary')) {
            CustomerAccount::where('customer_id', Auth::id())
                ->where('id', '!=', $account->id)
                ->update(['is_primary' => false]);
            $account->is_primary = true;
        }

        // إذا تم تعيين is_salary_account = true، نزيل الخاصية عن باقي الحسابات (اختياري)
        if ($request->boolean('is_salary_account')) {
            CustomerAccount::where('customer_id', Auth::id())
                ->where('id', '!=', $account->id)
                ->update(['is_salary_account' => false]);
            $account->is_salary_account = true;
        }

        // في حالة إرسال false (أي إلغاء التعيين)
        if ($request->has('is_primary') && !$request->boolean('is_primary')) {
            $account->is_primary = false;
        }
        if ($request->has('is_salary_account') && !$request->boolean('is_salary_account')) {
            $account->is_salary_account = false;
        }

        $account->save();

        return redirect()->route('customer.accounts.index')
            ->with('success', 'تم تحديث إعدادات الحساب بنجاح.');
    }

    /**
     * حذف حساب بنكي (إلغاء الربط)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $account = CustomerAccount::where('customer_id', Auth::id())->findOrFail($id);

        // منع حذف الحساب الأساسي إذا كان هو الوحيد؟ (اختياري)
        if ($account->is_primary) {
            // التحقق من وجود حسابات أخرى
            $otherAccounts = CustomerAccount::where('customer_id', Auth::id())
                ->where('id', '!=', $account->id)
                ->count();
            if ($otherAccounts == 0) {
                return redirect()->back()->with('error', 'لا يمكن حذف الحساب الأساسي الوحيد. قم بتعيين حساب آخر كأساسي أولاً.');
            }
        }

        // يمكن إضافة استدعاء لـ Lean API لإلغاء الربط (اختياري)
        // $leanService->disconnectAccount($account->provider_account_id);

        $account->delete();

        return redirect()->route('customer.accounts.index')
            ->with('success', 'تم حذف الحساب البنكي بنجاح.');
    }

    /**
     * تحديث رصيد الحساب (استعلام يدوي)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshBalance($id)
    {
        $account = CustomerAccount::where('customer_id', Auth::id())->findOrFail($id);

        try {
            $leanService = app(\App\Services\OpenBanking\LeanService::class);
            // نحتاج access token للمستخدم (قد يكون مخزناً أو نستخدم refresh token)
            // للتبسيط، نفترض أن leanService يمكنه جلب الرصيد باستخدام account provider id
            $balance = $leanService->getAccountBalance($account->provider_account_id);
            $account->current_balance = $balance;
            $account->save();

            return redirect()->back()->with('success', 'تم تحديث الرصيد بنجاح.');
        } catch (\Exception $e) {
            Log::error('Failed to refresh balance', ['account_id' => $account->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'فشل تحديث الرصيد. يرجى المحاولة لاحقاً.');
        }
    }
}
