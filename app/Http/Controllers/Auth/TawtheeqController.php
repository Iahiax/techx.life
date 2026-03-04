<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrgUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * وحدة التحكم في تسجيل دخول المنشآت عبر خدمة توثيق (Tawtheeq)
 * 
 * تقوم هذه الوحدة بتوجيه المستخدم إلى بوابة توثيق، واستقبال الرد،
 * ثم إنشاء أو تحديث بيانات المنشأة والمستخدم في قاعدة البيانات.
 */
class TawtheeqController extends Controller
{
    /**
     * إعادة توجيه المستخدم إلى بوابة توثيق للمصادقة
     */
    public function redirect()
    {
        // بناء رابط المصادقة باستخدام معلمات OAuth
        $query = http_build_query([
            'client_id' => config('services.tawtheeq.client_id'),
            'redirect_uri' => config('services.tawtheeq.redirect'),
            'response_type' => 'code',
            'scope' => 'organization', // نطلب صلاحية الوصول لبيانات المنشأة
        ]);

        // إعادة التوجيه إلى بوابة توثيق
        return redirect('https://tawtheeq.sa/auth?' . $query);
    }

    /**
     * معالجة الرد القادم من بوابة توثيق (Callback)
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            // التحقق من وجود رمز الخطأ
            if ($request->has('error')) {
                Log::error('خطأ في توثيق', ['error' => $request->get('error')]);
                return redirect()->route('landing')->with('error', 'فشل تسجيل الدخول عبر توثيق');
            }

            // الحصول على رمز التفويض
            $code = $request->get('code');

            // تبادل الرمز للحصول على رمز الوصول (access token)
            $tokenResponse = Http::post('https://tawtheeq.sa/token', [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.tawtheeq.client_id'),
                'client_secret' => config('services.tawtheeq.client_secret'),
                'code' => $code,
                'redirect_uri' => config('services.tawtheeq.redirect'),
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('فشل الحصول على رمز الوصول من توثيق', ['response' => $tokenResponse->body()]);
                return redirect()->route('landing')->with('error', 'فشل الاتصال بخدمة توثيق');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];

            // استعلام عن بيانات المستخدم والمنشأة من توثيق
            $userInfoResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://tawtheeq.sa/userinfo');

            if (!$userInfoResponse->successful()) {
                Log::error('فشل الحصول على بيانات المستخدم من توثيق');
                return redirect()->route('landing')->with('error', 'فشل الحصول على بيانات المستخدم');
            }

            $userInfo = $userInfoResponse->json();

            // بيانات المستخدم (المسؤول عن المنشأة)
            $nationalId = $userInfo['nationalId'];       // رقم الهوية الوطنية للمسؤول
            $fullName = $userInfo['fullName'];           // الاسم الكامل
            $phone = $userInfo['mobile'] ?? null;        // رقم الجوال (قد لا يكون متاحاً)

            // بيانات المنشأة
            $crNumber = $userInfo['crNumber'];           // رقم السجل التجاري
            $orgName = $userInfo['organizationName'];    // اسم المنشأة
            $orgType = $this->mapOrganizationType($userInfo['activity'] ?? ''); // تعيين نوع المنشأة

            // البحث عن المستخدم في قاعدة البيانات أو إنشاؤه
            $user = User::updateOrCreate(
                ['national_id' => $nationalId],
                [
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'auth_provider' => 'tawtheeq',
                    'provider_id' => $userInfo['sub'], // المعرف الفريد من توثيق
                    'type' => 'org_user', // هذا المستخدم تابع لمنشأة
                ]
            );

            // البحث عن المنشأة في قاعدة البيانات أو إنشاؤها
            $organization = Organization::updateOrCreate(
                ['cr_number' => $crNumber],
                [
                    'name' => $orgName,
                    'type' => $orgType,
                    'is_trusted' => false, // يتم تعيين الثقة يدوياً لاحقاً
                ]
            );

            // ربط المستخدم بالمنشأة (إذا لم يكن مرتبطاً بالفعل)
            $orgUser = OrgUser::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'org_id' => $organization->id,
                ],
                [
                    'role' => 'OrgAdmin', // المسؤول الأول يصبح مديراً
                ]
            );

            // تسجيل دخول المستخدم
            Auth::login($user, true);

            // التوجيه إلى لوحة تحكم المنشأة
            return redirect()->intended('/organization/dashboard');

        } catch (\Exception $e) {
            Log::error('استثناء في توثيق callback', ['message' => $e->getMessage()]);
            return redirect()->route('landing')->with('error', 'حدث خطأ غير متوقع. الرجاء المحاولة لاحقاً.');
        }
    }

    /**
     * تحويل النشاط المسجل في توثيق إلى نوع المنشأة المستخدم في النظام
     * 
     * @param string $activity
     * @return string
     */
    private function mapOrganizationType($activity)
    {
        // يمكن توسيع هذه الخريطة حسب الحاجة
        $activity = strtolower($activity);
        
        if (str_contains($activity, 'تمويل') || str_contains($activity, 'finance')) {
            return 'funding';
        } elseif (str_contains($activity, 'تأجير') || str_contains($activity, 'lease')) {
            return 'leasing';
        } elseif (str_contains($activity, 'حكومي') || str_contains($activity, 'government')) {
            return 'government';
        } elseif (str_contains($activity, 'كهرباء') || str_contains($activity, 'مياه') || str_contains($activity, 'اتصالات')) {
            return 'utility';
        } elseif (str_contains($activity, 'فاتورة') || str_contains($activity, 'billing')) {
            return 'billing';
        } else {
            return 'other'; // نوع افتراضي
        }
    }
}
