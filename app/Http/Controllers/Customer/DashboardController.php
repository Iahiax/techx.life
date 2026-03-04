<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * وحدة التحكم في لوحة تحكم العميل
 * 
 * تعرض للعميل نظرة عامة سريعة على حساباته، عقوده، واستقطاعاته الأخيرة.
 */
class DashboardController extends Controller
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
     * عرض الصفحة الرئيسية للوحة تحكم العميل
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // إحصائيات سريعة
        $stats = [
            'total_contracts' => $user->contracts()->count(),
            'active_contracts' => $user->contracts()->where('status', 'active')->count(),
            'pending_contracts' => $user->contracts()->where('status', 'pending_approval')->count(),
            'total_paid' => $user->deductions()->where('status', 'success')->sum('amount'),
            'total_fees' => $user->deductions()->where('status', 'success')->sum('platform_fee'),
            'remaining_amount' => $user->contracts()->sum('remaining_amount'),
        ];

        // آخر 5 استقطاعات
        $latest_deductions = $user->deductions()
            ->with('organization')
            ->latest()
            ->limit(5)
            ->get();

        // آخر 5 عقود
        $latest_contracts = $user->contracts()
            ->with('organization')
            ->latest()
            ->limit(5)
            ->get();

        // الإشعارات غير المقروءة
        $unread_notifications = $user->notifications()
            ->where('is_read', false)
            ->latest()
            ->limit(5)
            ->get();

        // عدد الحسابات البنكية المرتبطة
        $accounts_count = $user->bankAccounts()->count();

        return view('customer.dashboard', compact(
            'stats',
            'latest_deductions',
            'latest_contracts',
            'unread_notifications',
            'accounts_count'
        ));
    }
}
