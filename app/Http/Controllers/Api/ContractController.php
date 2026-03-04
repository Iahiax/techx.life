<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\User;
use App\Models\Consent;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * وحدة التحكم في إدارة العقود عبر API للجهات الخارجية
 * 
 * توفر هذه الوحدة واجهة برمجية تسمح للجهات (شركات التمويل، الحكومة، إلخ)
 * بإنشاء عقود جديدة، والاستعلام عن حالة العقود الخاصة بهم.
 * جميع الطرق محمية بالمصادقة عبر Sanctum وتتطلب أن يكون المستخدم تابعاً لمنشأة.
 */
class ContractController extends Controller
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
        // المستخدم قد يكون تابعاً لمنشأة واحدة أو أكثر، نأخذ أول منشأة له
        // يمكن تعديل المنطق حسب متطلبات العمل
        $orgUser = $user->orgUsers()->with('organization')->first();
        return $orgUser ? $orgUser->organization : null;
    }

    /**
     * عرض قائمة العقود الخاصة بالجهة (المنشأة) التي ينتمي إليها المستخدم
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

        $query = Contract::where('org_id', $organization->id)
            ->with(['customer' => function ($q) {
                $q->select('id', 'full_name', 'national_id', 'phone', 'email');
            }]);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب رقم العقد
        if ($request->filled('contract_number')) {
            $query->where('contract_number', 'like', '%' . $request->contract_number . '%');
        }

        // فلترة حسب العميل (رقم الهوية)
        if ($request->filled('national_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('national_id', $request->national_id);
            });
        }

        // ترتيب تنازلي
        $query->latest();

        $contracts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $contracts
        ]);
    }

    /**
     * إنشاء عقد جديد
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $organization = $this->getUserOrganization($user);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس تابعاً لأي منشأة.'
            ], 403);
        }

        // التحقق من أن المنشأة لديها اشتراك نشط (اختياري)
        // يمكن إضافة شرط للتأكد من صلاحية الاشتراك

        $validator = Validator::make($request->all(), [
            'customer_national_id' => 'required|string|exists:users,national_id',
            'contract_number' => 'required|string|unique:contracts,contract_number',
            'contract_type' => 'required|in:funding,leasing,government_fee,utility_bill,subscription,personal_loan,other',
            'principal_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'monthly_installment' => 'nullable|numeric|min:0',
            'installment_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'raw_contract_data' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // البحث عن العميل برقم الهوية
        $customer = User::where('national_id', $request->customer_national_id)
            ->where('type', 'customer')
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود أو ليس من نوع فرد.'
            ], 404);
        }

        // حساب المبلغ المتبقي (يساوي إجمالي المبلغ في البداية)
        $remaining_amount = $request->total_amount;

        // حساب عدد الأقساط المتبقية
        $remaining_installments = $request->installment_count;

        // إنشاء العقد
        $contract = Contract::create([
            'org_id' => $organization->id,
            'customer_id' => $customer->id,
            'contract_number' => $request->contract_number,
            'contract_type' => $request->contract_type,
            'principal_amount' => $request->principal_amount,
            'total_amount' => $request->total_amount,
            'paid_amount' => 0,
            'remaining_amount' => $remaining_amount,
            'monthly_installment' => $request->monthly_installment,
            'installment_count' => $request->installment_count,
            'paid_installments' => 0,
            'remaining_installments' => $remaining_installments,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending_approval',
            'raw_contract_data' => $request->raw_contract_data,
        ]);

        // إنشاء موافقة (consent) بحالة معلقة
        Consent::create([
            'customer_id' => $customer->id,
            'org_id' => $organization->id,
            'contract_id' => $contract->id,
            'consent_type' => $this->mapContractTypeToConsentType($request->contract_type),
            'status' => 'pending',
        ]);

        // إنشاء إشعار للعميل
        Notification::create([
            'user_id' => $customer->id,
            'title' => 'عقد جديد بانتظار موافقتك',
            'body' => 'تم إنشاء عقد جديد من قبل ' . $organization->name . ' برقم ' . $contract->contract_number . '. يرجى مراجعته والموافقة عليه.',
            'type' => 'contract_approval',
            'contract_id' => $contract->id,
        ]);

        // تسجيل العملية
        Log::info('تم إنشاء عقد جديد عبر API', [
            'contract_id' => $contract->id,
            'org_id' => $organization->id,
            'customer_id' => $customer->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء العقد بنجاح، وهو بانتظار موافقة العميل.',
            'data' => $contract->load('customer')
        ], 201);
    }

    /**
     * عرض تفاصيل عقد محدد
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

        $contract = Contract::where('org_id', $organization->id)
            ->with(['customer', 'deductions' => function ($q) {
                $q->latest()->limit(10);
            }])
            ->find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود أو لا ينتمي لمنشأتك.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $contract
        ]);
    }

    /**
     * تحديث بيانات عقد (مسموح فقط لبعض الحقول وبحالات محدودة)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $organization = $this->getUserOrganization($user);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس تابعاً لأي منشأة.'
            ], 403);
        }

        $contract = Contract::where('org_id', $organization->id)->find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود أو لا ينتمي لمنشأتك.'
            ], 404);
        }

        // لا يمكن تعديل العقد إلا إذا كان في حالة pending_approval
        if ($contract->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل عقد بعد موافقة العميل.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'total_amount' => 'sometimes|numeric|min:0',
            'monthly_installment' => 'sometimes|numeric|min:0',
            'installment_count' => 'sometimes|integer|min:1',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'raw_contract_data' => 'sometimes|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // تحديث الحقول المسموح بها
        if ($request->has('total_amount')) {
            $contract->total_amount = $request->total_amount;
            $contract->remaining_amount = $request->total_amount - $contract->paid_amount;
        }
        if ($request->has('monthly_installment')) {
            $contract->monthly_installment = $request->monthly_installment;
        }
        if ($request->has('installment_count')) {
            $contract->installment_count = $request->installment_count;
            $contract->remaining_installments = $request->installment_count - $contract->paid_installments;
        }
        if ($request->has('end_date')) {
            $contract->end_date = $request->end_date;
        }
        if ($request->has('raw_contract_data')) {
            $contract->raw_contract_data = $request->raw_contract_data;
        }

        $contract->save();

        // إرسال إشعار للعميل بالتحديث
        Notification::create([
            'user_id' => $contract->customer_id,
            'title' => 'تم تحديث بيانات العقد',
            'body' => 'تم تحديث بيانات العقد رقم ' . $contract->contract_number . '. يرجى مراجعته.',
            'type' => 'contract_update',
            'contract_id' => $contract->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العقد بنجاح.',
            'data' => $contract
        ]);
    }

    /**
     * حذف عقد (إذا كان لا يزال معلقاً)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $organization = $this->getUserOrganization($user);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس تابعاً لأي منشأة.'
            ], 403);
        }

        $contract = Contract::where('org_id', $organization->id)->find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود أو لا ينتمي لمنشأتك.'
            ], 404);
        }

        // لا يمكن حذف عقد نشط
        if ($contract->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف عقد نشط.'
            ], 403);
        }

        // حذف الموافقات المرتبطة أولاً (اختياري)
        $contract->consents()->delete();

        // حذف العقد (حذف فعلي)
        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العقد بنجاح.'
        ]);
    }

    /**
     * تحويل نوع العقد إلى نوع الموافقة المناسب
     *
     * @param string $contractType
     * @return string
     */
    private function mapContractTypeToConsentType($contractType)
    {
        $map = [
            'funding' => 'salary_deduction',
            'leasing' => 'salary_deduction',
            'government_fee' => 'general_billing',
            'utility_bill' => 'general_billing',
            'subscription' => 'account_deduction',
            'personal_loan' => 'salary_deduction',
            'other' => 'account_deduction',
        ];

        return $map[$contractType] ?? 'account_deduction';
    }
}
