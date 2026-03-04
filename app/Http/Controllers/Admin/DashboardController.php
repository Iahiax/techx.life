<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Deduction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * وحدة التحكم في لوحة تحكم المشرف العام
 * 
 * تعرض إحصائيات شاملة عن النظام: عدد المستخدمين، المنشآت، العقود، الاستقطاعات،
 * وأحدث العمليات.
 */
class DashboardController extends Controller
{
    /**
     * عرض الصفحة الرئيسية للوحة تحكم المشرف
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // إحصائيات سريعة
        $stats = [
            'total_users' => User::count(),
            'total_customers' => User::where('type', 'customer')->count(),
            'total_org_users' => User::where('type', 'org_user')->count(),
            'total_organizations' => Organization::count(),
            'total_contracts' => Contract::count(),
            'active_contracts' => Contract::where('status', 'active')->count(),
            'pending_contracts' => Contract::where('status', 'pending_approval')->count(),
            'total_deductions' => Deduction::count(),
            'successful_deductions' => Deduction::where('status', 'success')->count(),
            'failed_deductions' => Deduction::where('status', 'failed')->count(),
        ];

        // إجمالي المبالغ المحصلة من الاستقطاعات الناجحة
        $stats['total_collected'] = Deduction::where('status', 'success')
            ->sum('amount');

        // إجمالي العمولات المحصلة (1%)
        $stats['total_fees'] = Deduction::where('status', 'success')
            ->sum('platform_fee');

        // أحدث 10 استقطاعات
        $latest_deductions = Deduction::with(['customer', 'organization'])
            ->latest()
            ->limit(10)
            ->get();

        // أحدث 10 عقود
        $latest_contracts = Contract::with(['customer', 'organization'])
            ->latest()
            ->limit(10)
            ->get();

        // إحصائيات شهرية للاستقطاعات (آخر 6 أشهر)
        $monthly_stats = Deduction::select(
                DB::raw('YEAR(scheduled_date) as year'),
                DB::raw('MONTH(scheduled_date) as month'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(platform_fee) as total_fees')
            )
            ->where('scheduled_date', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('admin.dashboard', compact('stats', 'latest_deductions', 'latest_contracts', 'monthly_stats'));
    }
}
