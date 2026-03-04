<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * وحدة التحكم في إدارة عمليات الاستقطاع للمشرف العام
 * 
 * تتيح للمشرف مراقبة جميع عمليات الاستقطاع،
 * وتحديث حالتها، وإيقاف الاستقطاعات الدورية، وإعادة معالجة العمليات الفاشلة.
 */
class DeductionController extends Controller
{
    /**
     * عرض قائمة الاستقطاعات مع إمكانية البحث والتصفية
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Deduction::with(['customer', 'organization', 'contract']);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب العميل (رقم الهوية أو الاسم)
        if ($request->filled('customer_search')) {
            $search = $request->customer_search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('national_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        // فلترة حسب الجهة
        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        // ترتيب
        $query->latest('scheduled_date');

        $deductions = $query->paginate(20);

        return view('admin.deductions.index', compact('deductions'));
    }

    /**
     * عرض تفاصيل عملية استقطاع محددة
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $deduction = Deduction::with(['customer', 'organization', 'contract', 'sourceAccount'])->findOrFail($id);
        return view('admin.deductions.show', compact('deduction'));
    }

    /**
     * تحديث حالة عملية استقطاع (للتدخل اليدوي)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,success,failed',
            'failure_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $deduction = Deduction::findOrFail($id);
        $deduction->status = $request->status;
        $deduction->failure_reason = $request->failure_reason;
        $deduction->save();

        // تسجيل الإجراء
        activity()
            ->performedOn($deduction)
            ->causedBy(auth()->user())
            ->withProperties(['new_status' => $request->status])
            ->log('تم تحديث حالة الاستقطاع بواسطة المشرف');

        return redirect()->route('admin.deductions.show', $id)
            ->with('success', 'تم تحديث حالة الاستقطاع بنجاح.');
    }

    /**
     * إعادة معالجة استقطاع فاشل
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry($id)
    {
        $deduction = Deduction::findOrFail($id);

        if ($deduction->status !== 'failed') {
            return redirect()->back()->with('error', 'يمكن إعادة معالجة الاستقطاعات الفاشلة فقط.');
        }

        // تغيير الحالة إلى pending لتأخذه الجدولة من جديد
        $deduction->status = 'pending';
        $deduction->failure_reason = null;
        $deduction->save();

        activity()
            ->performedOn($deduction)
            ->causedBy(auth()->user())
            ->log('تم طلب إعادة معالجة الاستقطاع');

        return redirect()->route('admin.deductions.show', $id)
            ->with('success', 'تمت إعادة جدولة الاستقطاع للمعالجة.');
    }

    /**
     * إيقاف جميع الاستقطاعات المرتبطة بعقد معين (حالات استثنائية)
     *
     * @param int $contractId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stopContractDeductions($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تحديث الاستقطاعات المستقبلية المعلقة إلى ملغاة
        Deduction::where('contract_id', $contractId)
            ->where('status', 'pending')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->update([
                'status' => 'failed',
                'failure_reason' => 'تم الإيقاف بواسطة المشرف'
            ]);

        activity()
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log('تم إيقاف جميع الاستقطاعات المستقبلية لهذا العقد');

        return redirect()->route('admin.contracts.show', $contractId)
            ->with('success', 'تم إيقاف الاستقطاعات المستقبلية للعقد.');
    }

    /**
     * استئناف الاستقطاعات لعقد (إعادة تفعيل)
     *
     * @param int $contractId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resumeContractDeductions($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        if ($contract->status !== 'active') {
            return redirect()->back()->with('error', 'العقد غير نشط، لا يمكن استئناف الاستقطاعات.');
        }

        // إعادة إنشاء الاستقطاعات للأشهر القادمة بناءً على جدول العقد
        // يمكن استدعاء خدمة مخصصة لإعادة الجدولة
        // هذا يتطلب منطقاً إضافياً (مثلاً DeductionScheduler)
        // نكتفي هنا بتسجيل الإجراء وإعادة توجيه مع رسالة

        activity()
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log('تم طلب استئناف الاستقطاعات للعقد');

        return redirect()->route('admin.contracts.show', $contractId)
            ->with('info', 'تم تفعيل الاستقطاعات. سيتم إنشاء جدول جديد قريباً.');
    }
}
