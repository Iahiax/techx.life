<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * وحدة التحكم في إدارة المستخدمين للمشرف العام
 * 
 * تتيح هذه الوحدة للمشرف العام عرض جميع المستخدمين (أفراد، موظفي منشآت، مشرفين)،
 * وإضافة مستخدمين جدد، وتعديل بياناتهم، وتعطيلهم، وحذفهم.
 */
class UserController extends Controller
{
    /**
     * عرض قائمة المستخدمين مع إمكانية البحث والتصفية
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = User::query();

        // فلترة حسب نوع المستخدم
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // فلترة حسب حالة التفعيل (إذا كان لدينا حقل is_active)
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'yes');
        }

        // بحث برقم الهوية أو الاسم أو البريد
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('national_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // ترتيب حسب الأحدث
        $query->latest();

        $users = $query->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * عرض نموذج إنشاء مستخدم جديد
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * تخزين مستخدم جديد في قاعدة البيانات
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'national_id' => 'required|string|unique:users,national_id',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'type' => 'required|in:customer,org_user,admin',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'national_id' => $request->national_id,
            'email' => $request->email,
            'phone' => $request->phone,
            'type' => $request->type,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active', true),
            // المصادقة ستكون يدوية عبر اسم المستخدم وكلمة المرور
            'auth_provider' => null,
            'provider_id' => null,
        ]);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('تم إنشاء مستخدم جديد بواسطة المشرف');

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    /**
     * عرض تفاصيل مستخدم محدد
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $user = User::with(['orgUsers.organization'])->findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    /**
     * عرض نموذج تعديل بيانات المستخدم
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * تحديث بيانات المستخدم
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'national_id' => 'required|string|unique:users,national_id,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $id,
            'type' => 'required|in:customer,org_user,admin',
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'full_name' => $request->full_name,
            'national_id' => $request->national_id,
            'email' => $request->email,
            'phone' => $request->phone,
            'type' => $request->type,
            'is_active' => $request->boolean('is_active', $user->is_active),
        ];

        // تحديث كلمة المرور فقط إذا تم إدخالها
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('تم تحديث بيانات المستخدم');

        return redirect()->route('admin.users.show', $id)
            ->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    /**
     * حذف المستخدم (حذف ناعم)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // منع حذف المستخدم نفسه (المشرف الحالي)
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'لا يمكنك حذف حسابك الخاص.');
        }

        // التحقق من عدم وجود عقود نشطة أو ارتباطات مهمة (اختياري)
        if ($user->type === 'customer' && $user->contracts()->where('status', 'active')->exists()) {
            return redirect()->back()->with('error', 'لا يمكن حذف هذا المستخدم لأنه لديه عقود نشطة.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'تم حذف المستخدم بنجاح.');
    }

    /**
     * عرض المستخدمين المحذوفين (الأرشيف)
     *
     * @return \Illuminate\View\View
     */
    public function trashed()
    {
        $users = User::onlyTrashed()->paginate(20);
        return view('admin.users.trashed', compact('users'));
    }

    /**
     * استعادة مستخدم محذوف
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users.trashed')
            ->with('success', 'تم استعادة المستخدم بنجاح.');
    }

    /**
     * تفعيل أو تعطيل المستخدم
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // منع تعطيل المستخدم نفسه
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'لا يمكنك تغيير حالة حسابك الخاص.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'مفعل' : 'معطل';

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("تم تغيير حالة المستخدم إلى {$status}");

        return redirect()->back()->with('success', "تم تغيير حالة المستخدم: {$status}");
    }

    /**
     * تسجيل الدخول كمستخدم (انتحال الشخصية) - للمساعدة
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function impersonate($id)
    {
        $user = User::findOrFail($id);

        // منع انتحال المشرفين الآخرين (اختياري)
        if ($user->type === 'admin' && $user->id !== auth()->id()) {
            return redirect()->back()->with('error', 'لا يمكنك انتحال شخصية مشرف آخر.');
        }

        // تخزين معرف المشرف الأصلي في الجلسة لتمكين العودة
        session()->put('impersonated_by', auth()->id());
        session()->put('impersonated_user', $user->id);

        auth()->login($user);

        return redirect()->route('landing')->with('success', 'أنت الآن مسجل الدخول كمستخدم: ' . $user->full_name);
    }

    /**
     * العودة من انتحال الشخصية إلى الحساب الأصلي
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveImpersonation()
    {
        $originalUserId = session()->pull('impersonated_by');
        session()->pull('impersonated_user');

        if ($originalUserId) {
            auth()->loginUsingId($originalUserId);
            return redirect()->route('admin.users.index')->with('success', 'تم العودة إلى حسابك الأصلي.');
        }

        return redirect()->route('landing');
    }
}
