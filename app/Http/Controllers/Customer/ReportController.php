<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Deduction;
use Barryvdh\Dompdf\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * وحدة التحكم في تقارير العميل (PDF)
 * 
 * تتيح هذه الوحدة للعميل طباعة تقارير مختلفة بصيغة PDF،
 * مثل تقرير بجميع عقوده أو تقرير بالاستقطاعات.
 */
class ReportController extends Controller
{
    /**
     * إنشاء مثيل جديد مع تطبيق middleware المصادقة والتحقق من نوع المستخدم
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->type !== 'customer') {
                abort(403, 'هذه الصفحة مخصصة للعملاء فقط.');
            }
            return $next($request);
        });
    }

    /**
     * عرض صفحة التقارير المتاحة (اختياري)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('customer.reports.index');
    }

    /**
     * تقرير جميع العقود الخاصة بالعميل
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function contractsReport(Request $request)
    {
        $user = Auth::user();

        $query = Contract::where('customer_id', $user->id)
            ->with('organization');

        // فلترة حسب الحالة إذا وجدت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->orderBy('created_at', 'desc')->get();

        // إحصائيات سريعة
        $totalAmount = $contracts->sum('total_amount');
        $paidAmount = $contracts->sum('paid_amount');
        $remainingAmount = $contracts->sum('remaining_amount');

        $data = [
            'user' => $user,
            'contracts' => $contracts,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $contracts->count(),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
        ];

        $pdf = Pdf::loadView('pdf.customer.contracts', $data);
        return $pdf->download('تقرير_العقود_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * تقرير جميع الاستقطاعات الخاصة بالعميل
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function deductionsReport(Request $request)
    {
        $user = Auth::user();

        $query = Deduction::where('customer_id', $user->id)
            ->with(['organization', 'contract']);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        $deductions = $query->orderBy('scheduled_date', 'desc')->get();

        // إحصائيات
        $successful = $deductions->where('status', 'success');
        $totalAmount = $successful->sum('amount');
        $totalFees = $successful->sum('platform_fee');

        $data = [
            'user' => $user,
            'deductions' => $deductions,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $deductions->count(),
            'success_count' => $successful->count(),
            'failed_count' => $deductions->where('status', 'failed')->count(),
            'total_amount' => $totalAmount,
            'total_fees' => $totalFees,
        ];

        $pdf = Pdf::loadView('pdf.customer.deductions', $data);
        return $pdf->download('تقرير_الاستقطاعات_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * تقرير مفصل لعقد معين مع سجل استقطاعاته
     *
     * @param int $contractId
     * @return \Illuminate\Http\Response
     */
    public function singleContractReport($contractId)
    {
        $user = Auth::user();

        $contract = Contract::where('customer_id', $user->id)
            ->with(['organization', 'deductions' => function ($q) {
                $q->latest();
            }])
            ->findOrFail($contractId);

        $data = [
            'user' => $user,
            'contract' => $contract,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = Pdf::loadView('pdf.customer.single_contract', $data);
        return $pdf->download('عقد_' . $contract->contract_number . '.pdf');
    }
}
