<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * وحدة التحكم في الاستعلام عن عمليات الاستقطاع عبر API للجهات الخارجية
 * 
 * توفر هذه الوحدة واجهة برمجية تسمح للجهات (شركات التمويل، الحكومة، إلخ)
 * بالاستعلام عن حالة الاستقطاعات الخاصة بهم.
 */
class DeductionController extends Controller
{
    /**
     * إنشاء مثيل جديد مع تطبيق middleware المصادقة
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * الحصول على المنشأة المرتبطة بالمستخدم الحالي
     *
     * @param \App\Models\User $user
     * @return \App\Models\Organization|null
     */
    private function getUserOrganization(User $user)
    {
        $orgUser = $user->orgUsers()->with('organization')->first();
        return $orgUser ? $orgUser->organization : null;
    }

    /**
     * عرض قائمة الاستقطاعات الخاصة بالجهة (المنشأة) التي ينتمي إليها المستخدم
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $organization = $this->getUserOrganization($user);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس تابعاً لأي منشأة.'
            ], 403);
        }

        $query = Deduction::where('org_id', $organization->id)
            ->with(['customer' => function ($q) {
                $q->select('id', 'full_name', 'national_id');
            }, 'contract' => function ($q) {
                $q->select('id', 'contract_number', 'contract_type');
            }]);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب التاريخ (من - إلى)
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        // فلترة حسب العقد (إذا أُرسل رقم العقد أو معرف العقد)
        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        // فلترة حسب العميل (رقم الهوية)
        if ($request->filled('national_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('national_id', $request->national_id);
            });
        }

        // ترتيب حسب تاريخ الاستقطاع (الأحدث أولاً)
        $query->latest('scheduled_date');

        $deductions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $deductions
        ]);
    }

    /**
     * عرض تفاصيل عملية استقطاع محددة
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $organization = $this->getUserOrganization($user);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس تابعاً لأي منشأة.'
            ], 403);
        }

        $deduction = Deduction::where('org_id', $organization->id)
            ->with(['customer', 'contract', 'sourceAccount'])
            ->find($id);

        if (!$deduction) {
            return response()->json([
                'success' => false,
                'message' => 'عملية الاستقطاع غير موجودة أو لا تنتمي لمنشأتك.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $deduction
        ]);
    }

    /**
     * الحصول على إحصائيات سريعة للاستقطاعات (للجهة)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $organization = $this->getUserOrganization($user);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس تابعاً لأي منشأة.'
            ], 403);
        }

        $stats = [
            'total_deductions' => Deduction::where('org_id', $organization->id)->count(),
            'successful_deductions' => Deduction::where('org_id', $organization->id)->where('status', 'success')->count(),
            'failed_deductions' => Deduction::where('org_id', $organization->id)->where('status', 'failed')->count(),
            'pending_deductions' => Deduction::where('org_id', $organization->id)->whereIn('status', ['pending', 'processing'])->count(),
            'total_amount_collected' => Deduction::where('org_id', $organization->id)->where('status', 'success')->sum('amount'),
            'total_fees_paid' => Deduction::where('org_id', $organization->id)->where('status', 'success')->sum('platform_fee'),
        ];

        // إضافة إحصائيات الشهر الحالي
        $currentMonth = now()->startOfMonth();
        $stats['current_month'] = [
            'count' => Deduction::where('org_id', $organization->id)
                ->where('status', 'success')
                ->where('scheduled_date', '>=', $currentMonth)
                ->count(),
            'amount' => Deduction::where('org_id', $organization->id)
                ->where('status', 'success')
                ->where('scheduled_date', '>=', $currentMonth)
                ->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
