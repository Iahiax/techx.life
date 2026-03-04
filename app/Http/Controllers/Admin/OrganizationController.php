<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Models\OrgUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * وحدة التحكم في إدارة المنشآت (الجهات) للمشرف العام
 * 
 * توفر هذه الوحدة عمليات CRUD للمنشآت، وإدارة اشتراكاتها،
 * وتعيين المستخدمين المسؤولين، وتفعيل أو تعطيل المنشأة.
 */
class OrganizationController extends Controller
{
    /**
     * عرض قائمة المنشآت مع إمكانية البحث والتصفية
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Organization::query();

        // فلترة حسب نوع المنشأة
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // فلترة حسب حالة الثقة (موثوقة أم لا)
        if ($request->filled('is_trusted')) {
            $query->where('is_trusted', $request->is_trusted === 'yes');
        }

        // بحث باسم المنشأة أو رقم السجل
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cr_number', 'like', "%{$search}%");
            });
        }

        // ترتيب
        $query->latest();

        $organizations = $query->paginate(20);

        return view('admin.organizations.index', compact('organizations'));
    }

    /**
     * عرض نموذج إنشاء منشأة جديدة
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.organizations.create');
    }

    /**
     * تخزين منشأة جديدة في قاعدة البيانات
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cr_number' => 'required|string|unique:organizations,cr_number',
            'type' => 'required|in:funding,leasing,government,billing,telecom,utility,individual_beneficiary,other',
            'is_trusted' => 'sometimes|boolean',
            'subscription_package' => 'nullable|in:small,medium,large,government',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $organization = Organization::create([
            'name' => $request->name,
            'cr_number' => $request->cr_number,
            'type' => $request->type,
            'is_trusted' => $request->boolean('is_trusted'),
            'subscription_package' => $request->subscription_package,
        ]);

        // إذا تم اختيار باقة، ننشئ اشتراكاً جديداً
        if ($request->filled('subscription_package')) {
            Subscription::create([
                'org_id' => $organization->id,
                'package' => $request->subscription_package,
                'price' => $this->getPackagePrice($request->subscription_package),
                'start_date' => now(),
                'end_date' => now()->addYear(), // افتراضي سنة
                'status' => 'active',
            ]);
        }

        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user())
            ->log('تم إنشاء منشأة جديدة بواسطة المشرف');

        return redirect()->route('admin.organizations.show', $organization->id)
            ->with('success', 'تم إنشاء المنشأة بنجاح.');
    }

    /**
     * عرض تفاصيل منشأة محددة
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $organization = Organization::with(['users.user', 'subscriptions', 'accounts'])->findOrFail($id);
        return view('admin.organizations.show', compact('organization'));
    }

    /**
     * عرض نموذج تعديل المنشأة
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $organization = Organization::findOrFail($id);
        return view('admin.organizations.edit', compact('organization'));
    }

    /**
     * تحديث بيانات المنشأة
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cr_number' => 'required|string|unique:organizations,cr_number,' . $id,
            'type' => 'required|in:funding,leasing,government,billing,telecom,utility,individual_beneficiary,other',
            'is_trusted' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $organization->update([
            'name' => $request->name,
            'cr_number' => $request->cr_number,
            'type' => $request->type,
            'is_trusted' => $request->boolean('is_trusted'),
        ]);

        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user())
            ->log('تم تحديث بيانات المنشأة');

        return redirect()->route('admin.organizations.show', $id)
            ->with('success', 'تم تحديث بيانات المنشأة بنجاح.');
    }

    /**
     * حذف المنشأة (حذف ناعم)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $organization = Organization::findOrFail($id);

        // التحقق من وجود عقود نشطة للمنشأة
        if ($organization->contracts()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->with('error', 'لا يمكن حذف المنشأة لأن لديها عقود نشطة.');
        }

        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', 'تم حذف المنشأة بنجاح.');
    }

    /**
     * عرض المنشآت المحذوفة (الأرشيف)
     *
     * @return \Illuminate\View\View
     */
    public function trashed()
    {
        $organizations = Organization::onlyTrashed()->paginate(20);
        return view('admin.organizations.trashed', compact('organizations'));
    }

    /**
     * استعادة منشأة محذوفة
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $organization = Organization::onlyTrashed()->findOrFail($id);
        $organization->restore();

        return redirect()->route('admin.organizations.trashed')
            ->with('success', 'تم استعادة المنشأة بنجاح.');
    }

    /**
     * تفعيل أو تعطيل حالة الثقة للمنشأة
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleTrusted($id)
    {
        $organization = Organization::findOrFail($id);
        $organization->is_trusted = !$organization->is_trusted;
        $organization->save();

        $status = $organization->is_trusted ? 'موثوقة' : 'غير موثوقة';

        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user())
            ->log("تم تغيير حالة الثقة إلى {$status}");

        return redirect()->back()->with('success', "تم تغيير حالة الثقة: {$status}");
    }

    /**
     * إضافة مستخدم (مسؤول) إلى المنشأة
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addUser(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:OrgAdmin,OrgUser',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // التحقق من عدم تكرار الربط
        $exists = OrgUser::where('user_id', $request->user_id)
            ->where('org_id', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'هذا المستخدم مضاف بالفعل إلى المنشأة.');
        }

        OrgUser::create([
            'user_id' => $request->user_id,
            'org_id' => $id,
            'role' => $request->role,
        ]);

        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user())
            ->log("تم إضافة مستخدم إلى المنشأة بدور {$request->role}");

        return redirect()->route('admin.organizations.show', $id)
            ->with('success', 'تم إضافة المستخدم إلى المنشأة.');
    }

    /**
     * إزالة مستخدم من المنشأة
     *
     * @param int $orgId
     * @param int $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeUser($orgId, $userId)
    {
        $orgUser = OrgUser::where('org_id', $orgId)->where('user_id', $userId)->firstOrFail();
        $orgUser->delete();

        activity()
            ->causedBy(auth()->user())
            ->log("تم إزالة المستخدم من المنشأة");

        return redirect()->route('admin.organizations.show', $orgId)
            ->with('success', 'تمت إزالة المستخدم من المنشأة.');
    }

    /**
     * إنشاء اشتراك جديد للمنشأة
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createSubscription(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'package' => 'required|in:small,medium,large,government',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // إنهاء أي اشتراكات نشطة حالية (اختياري)
        Subscription::where('org_id', $id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        // إنشاء الاشتراك الجديد
        Subscription::create([
            'org_id' => $id,
            'package' => $request->package,
            'price' => $this->getPackagePrice($request->package),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'active',
        ]);

        // تحديث حقل subscription_package في جدول المنشآت
        $organization->subscription_package = $request->package;
        $organization->save();

        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user())
            ->log("تم إنشاء اشتراك جديد ({$request->package})");

        return redirect()->route('admin.organizations.show', $id)
            ->with('success', 'تم إنشاء الاشتراك بنجاح.');
    }

    /**
     * الحصول على سعر الباقة (يمكن جلبها من إعدادات أو جدول منفصل)
     *
     * @param string $package
     * @return float
     */
    private function getPackagePrice($package)
    {
        return match($package) {
            'small' => 299,
            'medium' => 599,
            'large' => 999,
            'government' => 0, // مجاني أو حسب الاتفاق
            default => 0,
        };
    }
}
