<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * وحدة التحكم في إدارة العقود للمشرف العام
 * 
 * تتيح هذه الوحدة للمشرف العام عرض جميع العقود،
 * وتفاصيل كل عقد، وتحديث حالة العقد، وحذف العقود (إذا لزم الأمر).
 */
class ContractController extends Controller
{
    /**
     * عرض قائمة العقود مع إمكانية البحث والتصفية
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Contract::with(['customer', 'organization']);

        // تصفية حسب حالة العقد
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // تصفية حسب العميل (البحث برقم الهوية أو الاسم)
        if ($request->filled('customer_search')) {
            $search = $request->customer_search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('national_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        // تصفية حسب الجهة (المنشأة)
        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        // تصفية حسب نوع العقد
        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        // تصفية حسب تاريخ الإنشاء
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // ترتيب حسب الأحدث
        $query->latest();

        $contracts = $query->paginate(20);

        return view('admin.contracts.index', compact('contracts'));
    }

    /**
     * عرض تفاصيل عقد محدد
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $contract = Contract::with(['customer', 'organization', 'deductions'])->findOrFail($id);
        return view('admin.contracts.show', compact('contract'));
    }

    /**
     * تحديث حالة العقد
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending_approval,active,rejected,closed,defaulted',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $contract = Contract::findOrFail($id);
        $oldStatus = $contract->status;
        $contract->status = $request->status;
        $contract->save();

        // تسجيل الإجراء في السجل (اختياري)
        activity()
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->withProperties(['old_status' => $oldStatus, 'new_status' => $request->status, 'reason' => $request->reason])
            ->log('تم تغيير حالة العقد بواسطة المشرف');

        return redirect()->route('admin.contracts.show', $id)
            ->with('success', 'تم تحديث حالة العقد بنجاح.');
    }

    /**
     * حذف العقد (حذف ناعم)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);

        // التحقق من إمكانية الحذف (مثلاً لا يمكن حذف عقود نشطة)
        if ($contract->status === 'active') {
            return redirect()->route('admin.contracts.index')
                ->with('error', 'لا يمكن حذف عقد نشط. يجب إنهاؤه أولاً.');
        }

        $contract->delete();

        return redirect()->route('admin.contracts.index')
            ->with('success', 'تم حذف العقد بنجاح.');
    }

    /**
     * عرض العقود المحذوفة (الأرشيف)
     *
     * @return \Illuminate\View\View
     */
    public function trashed()
    {
        $contracts = Contract::onlyTrashed()->with(['customer', 'organization'])->paginate(20);
        return view('admin.contracts.trashed', compact('contracts'));
    }

    /**
     * استعادة عقد محذوف
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $contract = Contract::onlyTrashed()->findOrFail($id);
        $contract->restore();

        return redirect()->route('admin.contracts.trashed')
            ->with('success', 'تم استعادة العقد بنجاح.');
    }
}
