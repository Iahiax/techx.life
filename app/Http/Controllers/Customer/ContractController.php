<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Consent;
use App\Models\Notification;
use Barryvdh\Dompdf\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * وحدة التحكم في إدارة العقود للعميل (الفرد)
 * 
 * تتيح هذه الوحدة للعميل عرض جميع عقوده (المعلقة، النشطة، المنتهية)،
 * والموافقة على العقود الجديدة أو رفضها، ومشاهدة تفاصيل العقد وسجل الاستقطاعات.
 */
class ContractController extends Controller
{
    /**
     * إنشاء مثيل جديد مع تطبيق middleware المصادقة والتحقق من نوع المستخدم
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
     * عرض قائمة العقود الخاصة بالعميل
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Contract::where('customer_id', $user->id)
            ->with('organization'); // نأخذ بيانات الجهة

        // فلترة حسب الحالة إذا أرسلت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ترتيب حسب الأحدث
        $query->latest();

        $contracts = $query->paginate(15);

        return view('customer.contracts.index', compact('contracts'));
    }

    /**
     * عرض تفاصيل عقد محدد
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $user = Auth::user();

        $contract = Contract::where('customer_id', $user->id)
            ->with(['organization', 'deductions' => function ($q) {
                $q->latest()->limit(20); // آخر 20 استقطاع
            }])
            ->findOrFail($id);

        return view('customer.contracts.show', compact('contract'));
    }

    /**
     * عرض نموذج الموافقة على العقد (اختياري، قد نستخدم show نفسه)
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function review($id)
    {
        $user = Auth::user();

        $contract = Contract::where('customer_id', $user->id)
            ->where('status', 'pending_approval')
            ->with('organization')
            ->findOrFail($id);

        return view('customer.contracts.review', compact('contract'));
    }

    /**
     * الموافقة على عقد معلق
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $user = Auth::user();

        $contract = Contract::where('customer_id', $user->id)
            ->where('status', 'pending_approval')
            ->findOrFail($id);

        // تحديث حالة العقد
        $contract->status = 'active';
        $contract->save();

        // تحديث حالة الموافقة المرتبطة
        Consent::where('contract_id', $contract->id)
            ->where('customer_id', $user->id)
            ->update(['status' => 'approved']);

        // إنشاء إشعار للجهة (المنشأة) بأن العميل وافق
        // يمكن إرسال إشعار عبر Notification::create
        // لكن الجهة ليس لها مستخدمون محددون، لذا قد نكتفي بتحديث حالة العقد

        // (اختياري) إنشاء جدولة الاستقطاعات للأشهر القادمة
        // هذا يعتمد على DeductionScheduler
        // يمكن استدعاء خدمة خاصة بذلك

        return redirect()->route('customer.contracts.show', $contract->id)
            ->with('success', 'تمت الموافقة على العقد بنجاح. سيتم بدء الاستقطاعات حسب الجدول.');
    }

    /**
     * رفض عقد معلق
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject($id)
    {
        $user = Auth::user();

        $contract = Contract::where('customer_id', $user->id)
            ->where('status', 'pending_approval')
            ->findOrFail($id);

        // تحديث حالة العقد
        $contract->status = 'rejected';
        $contract->save();

        // تحديث حالة الموافقة
        Consent::where('contract_id', $contract->id)
            ->where('customer_id', $user->id)
            ->update(['status' => 'rejected']);

        // يمكن إضافة سبب الرفض إذا أردنا (قد نضيف حقل rejection_reason في جدول العقود)

        return redirect()->route('customer.contracts.index')
            ->with('success', 'تم رفض العقد.');
    }

    /**
     * طباعة تفاصيل العقد بصيغة PDF
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function printPdf($id)
    {
        $user = Auth::user();

        $contract = Contract::where('customer_id', $user->id)
            ->with(['organization', 'deductions' => function ($q) {
                $q->latest();
            }])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.customer.contract', compact('contract'));
        return $pdf->download('عقد_' . $contract->contract_number . '.pdf');
    }

    /**
     * عرض سجل الاستقطاعات لعقد معين (قد يكون في صفحة show نفسها)
     * ولكن نضيفها للاكتمال
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function deductions($id)
    {
        $user = Auth::user();

        $contract = Contract::where('customer_id', $user->id)->findOrFail($id);

        $deductions = $contract->deductions()->latest()->paginate(20);

        return view('customer.contracts.deductions', compact('contract', 'deductions'));
    }
}
