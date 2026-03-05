<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Deduction;
use Barryvdh\Dompdf\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('org-user');
    }

    private function getOrganization()
    {
        $user = Auth::user();
        return $user->organizations()->first();
    }

    public function index()
    {
        return view('organization.reports.index');
    }

    public function contractsReport(Request $request)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $query = Contract::where('org_id', $org->id)->with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $contracts = $query->get();
        $totalAmount = $contracts->sum('total_amount');
        $paidAmount = $contracts->sum('paid_amount');

        $data = [
            'organization' => $org,
            'contracts' => $contracts,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $contracts->count(),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
        ];

        $pdf = Pdf::loadView('pdf.organization.contracts_report', $data);
        return $pdf->download('تقرير_العقود_' . now()->format('Y-m-d') . '.pdf');
    }

    public function deductionsReport(Request $request)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $query = Deduction::where('org_id', $org->id)
            ->with(['customer', 'contract']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        $deductions = $query->get();
        $successful = $deductions->where('status', 'success');
        $totalAmount = $successful->sum('amount');
        $totalFees = $successful->sum('platform_fee');

        $data = [
            'organization' => $org,
            'deductions' => $deductions,
            'filters' => $request->all(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_count' => $deductions->count(),
            'success_count' => $successful->count(),
            'failed_count' => $deductions->where('status', 'failed')->count(),
            'total_amount' => $totalAmount,
            'total_fees' => $totalFees,
        ];

        $pdf = Pdf::loadView('pdf.organization.deductions_report', $data);
        return $pdf->download('تقرير_الاستقطاعات_' . now()->format('Y-m-d') . '.pdf');
    }

    public function revenueReport(Request $request)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $year = $request->get('year', now()->year);
        $month = $request->get('month');

        $query = Deduction::where('org_id', $org->id)
            ->where('status', 'success')
            ->whereYear('scheduled_date', $year);

        if ($month) {
            $query->whereMonth('scheduled_date', $month);
        }

        $deductions = $query->get();
        $totalAmount = $deductions->sum('amount');
        $totalFees = $deductions->sum('platform_fee');

        $data = [
            'organization' => $org,
            'year' => $year,
            'month' => $month,
            'deductions' => $deductions,
            'total_amount' => $totalAmount,
            'total_fees' => $totalFees,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = Pdf::loadView('pdf.organization.revenue_report', $data);
        return $pdf->download('تقرير_الإيرادات_' . $year . '.pdf');
    }
}
