<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Deduction;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\Dompdf\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * وحدة التحكم في التقارير الشاملة للمشرف العام
 * 
 * توفر هذه الوحدة إمكانية عرض وتحميل تقارير PDF شاملة عن النظام:
 * - تقرير المستخدمين (الأفراد والمنشآت)
 * - تقرير المنشآت (الجهات المسجلة)
 * - تقرير العقود (جميع العقود وتفاصيلها)
 * - تقرير الاستقطاعات (العمليات المالية)
 * - تقرير الإيرادات (العمولات المحصلة)
 */
class ReportController extends Controller
{
    /**
     * عرض صفحة التقارير (اختياري)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * تقرير المستخدمين
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function usersReport(Request $request)
    {
        $query = User::query();

        // فلترة حسب النوع إذا وجد
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $data = [
            'users' => $users,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $users->count(),
        ];

        $pdf = Pdf::loadView('pdf.admin.users_report', $data);
        return $pdf->download('تقرير_المستخدمين_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * تقرير المنشآت (الجهات)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function organizationsReport(Request $request)
    {
        $query = Organization::withCount('contracts');

        // فلترة حسب النوع
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // فلترة حسب الثقة
        if ($request->filled('is_trusted')) {
            $query->where('is_trusted', $request->is_trusted === 'yes');
        }

        $organizations = $query->orderBy('created_at', 'desc')->get();

        $data = [
            'organizations' => $organizations,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $organizations->count(),
        ];

        $pdf = Pdf::loadView('pdf.admin.organizations_report', $data);
        return $pdf->download('تقرير_المنشآت_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * تقرير العقود
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function contractsReport(Request $request)
    {
        $query = Contract::with(['customer', 'organization']);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب نوع العقد
        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        // فلترة حسب تاريخ الإنشاء
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $contracts = $query->orderBy('created_at', 'desc')->get();

        // إحصائيات
        $total_amount = $contracts->sum('total_amount');
        $paid_amount = $contracts->sum('paid_amount');
        $remaining_amount = $contracts->sum('remaining_amount');

        $data = [
            'contracts' => $contracts,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $contracts->count(),
            'total_amount' => $total_amount,
            'paid_amount' => $paid_amount,
            'remaining_amount' => $remaining_amount,
        ];

        $pdf = Pdf::loadView('pdf.admin.contracts_report', $data);
        return $pdf->download('تقرير_العقود_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * تقرير الاستقطاعات
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function deductionsReport(Request $request)
    {
        $query = Deduction::with(['customer', 'organization', 'contract']);

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

        // فلترة حسب الجهة
        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        $deductions = $query->orderBy('scheduled_date', 'desc')->get();

        // إحصائيات
        $total_amount = $deductions->where('status', 'success')->sum('amount');
        $total_fees = $deductions->where('status', 'success')->sum('platform_fee');
        $success_count = $deductions->where('status', 'success')->count();
        $failed_count = $deductions->where('status', 'failed')->count();

        $data = [
            'deductions' => $deductions,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $deductions->count(),
            'success_count' => $success_count,
            'failed_count' => $failed_count,
            'total_amount' => $total_amount,
            'total_fees' => $total_fees,
        ];

        $pdf = Pdf::loadView('pdf.admin.deductions_report', $data);
        return $pdf->download('تقرير_الاستقطاعات_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * تقرير الإيرادات (العمولات)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function revenueReport(Request $request)
    {
        // تقرير شهري أو سنوي
        $year = $request->get('year', now()->year);
        $month = $request->get('month');

        $query = Deduction::where('status', 'success')
            ->select(
                DB::raw('YEAR(scheduled_date) as year'),
                DB::raw('MONTH(scheduled_date) as month'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(platform_fee) as total_fees')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        if ($year) {
            $query->whereYear('scheduled_date', $year);
        }
        if ($month) {
            $query->whereMonth('scheduled_date', $month);
        }

        $monthly = $query->get();

        // إجمالي السنة
        $yearly_totals = Deduction::where('status', 'success')
            ->whereYear('scheduled_date', $year)
            ->select(
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(platform_fee) as total_fees'),
                DB::raw('COUNT(*) as total_count')
            )
            ->first();

        $data = [
            'monthly' => $monthly,
            'yearly' => $yearly_totals,
            'year' => $year,
            'month' => $month,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = Pdf::loadView('pdf.admin.revenue_report', $data);
        return $pdf->download('تقرير_الإيرادات_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * تقرير مالي شامل (بيان الدخل)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function financialStatement(Request $request)
    {
        $year = $request->get('year', now()->year);

        // إجمالي الاستقطاعات الناجحة والعمولات
        $total_deductions = Deduction::where('status', 'success')
            ->whereYear('scheduled_date', $year)
            ->sum('amount');

        $total_fees = Deduction::where('status', 'success')
            ->whereYear('scheduled_date', $year)
            ->sum('platform_fee');

        // عدد العقود النشطة
        $active_contracts = Contract::where('status', 'active')->count();

        // عدد المنشآت النشطة (لديها اشتراك ساري)
        $active_organizations = Organization::whereHas('subscriptions', function ($q) {
            $q->where('status', 'active');
        })->count();

        $data = [
            'year' => $year,
            'total_deductions' => $total_deductions,
            'total_fees' => $total_fees,
            'active_contracts' => $active_contracts,
            'active_organizations' => $active_organizations,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = Pdf::loadView('pdf.admin.financial_statement', $data);
        return $pdf->download('بيان_الدخل_' . $year . '.pdf');
    }
}
