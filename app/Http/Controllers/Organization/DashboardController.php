<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('org-user');
    }

    public function index()
    {
        $user = Auth::user();
        $organization = $user->organizations()->first();

        if (!$organization) {
            abort(403, 'لا توجد منشأة مرتبطة بهذا المستخدم.');
        }

        $stats = [
            'total_contracts' => $organization->contracts()->count(),
            'active_contracts' => $organization->contracts()->where('status', 'active')->count(),
            'pending_contracts' => $organization->contracts()->where('status', 'pending_approval')->count(),
            'total_deductions' => $organization->deductions()->count(),
            'total_collected' => $organization->deductions()->where('status', 'success')->sum('amount'),
            'total_fees' => $organization->deductions()->where('status', 'success')->sum('platform_fee'),
            'outstanding_amount' => $organization->contracts()->sum('remaining_amount'),
        ];

        $latest_deductions = $organization->deductions()
            ->with('customer')
            ->latest()
            ->limit(10)
            ->get();

        $latest_contracts = $organization->contracts()
            ->with('customer')
            ->latest()
            ->limit(10)
            ->get();

        $unread_notifications = $user->notifications()
            ->where('is_read', false)
            ->latest()
            ->limit(5)
            ->get();

        return view('organization.dashboard', compact('stats', 'latest_deductions', 'latest_contracts', 'unread_notifications'));
    }
}
