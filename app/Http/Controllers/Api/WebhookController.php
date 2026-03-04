<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

/**
 * وحدة التحكم في استقبال Webhooks من الخدمات الخارجية
 * 
 * تستقبل هذه الوحدة الإشعارات من:
 * - Lean (Open Banking) لتحديث حالة التحويلات وربط الحسابات
 * - سداد (Sadad) للإشعار بالفواتير الجديدة أو تحديث حالة الفواتير
 * - أي مزود آخر مستقبلاً
 */
class WebhookController extends Controller
{
    /**
     * مفتاح سري للتحقق من صحة الطلبات الواردة من Lean
     * يتم قراءته من ملف .env
     */
    protected $leanWebhookSecret;

    /**
     * مفتاح سري للتحقق من صحة الطلبات الواردة من سداد
     */
    protected $sadadWebhookSecret;

    public function __construct()
    {
        $this->leanWebhookSecret = config('services.lean.webhook_secret');
        $this->sadadWebhookSecret = config('services.sadad.webhook_secret');
    }

    /**
     * نقطة دخول واحدة لجميع Webhooks - تقوم بتوجيه الطلب للمعالج المناسب
     * بناءً على مسار الرأس أو محتوى الطلب
     *
     * @param Request $request
     * @param string $provider (اختياري) يمكن تحديد المزود في الرابط
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, $provider = null)
    {
        // تسجيل الطلب للتصحيح (يمكن إزالته في الإنتاج)
        Log::info('Webhook received', [
            'provider' => $provider,
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        // إذا لم يحدد المزود في الرابط، نحاول استنتاجه من الرأس
        if (!$provider) {
            $userAgent = $request->header('User-Agent');
            if (str_contains($userAgent, 'Lean')) {
                $provider = 'lean';
            } elseif (str_contains($userAgent, 'Sadad')) {
                $provider = 'sadad';
            } else {
                // قد نعتمد على محتوى JSON
                $data = $request->all();
                if (isset($data['provider']) && $data['provider'] === 'lean') {
                    $provider = 'lean';
                } elseif (isset($data['provider']) && $data['provider'] === 'sadad') {
                    $provider = 'sadad';
                } else {
                    Log::warning('Unknown webhook provider', ['request' => $request->all()]);
                    return response()->json(['error' => 'Unknown provider'], 400);
                }
            }
        }

        // توجيه للمعالج المناسب
        switch ($provider) {
            case 'lean':
                return $this->handleLeanWebhook($request);
            case 'sadad':
                return $this->handleSadadWebhook($request);
            default:
                Log::warning('Unsupported webhook provider', ['provider' => $provider]);
                return response()->json(['error' => 'Unsupported provider'], 400);
        }
    }

    /**
     * معالجة Webhooks القادمة من Lean
     * 
     * أنواع الأحداث المدعومة:
     * - transfer.updated (تحديث حالة تحويل)
     * - account.connected (تم ربط حساب جديد)
     * - account.disconnected (تم إلغاء ربط حساب)
     * - transfer.created (تم إنشاء تحويل جديد - قد لا نحتاجه)
     * - transfer.failed (فشل تحويل)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleLeanWebhook(Request $request)
    {
        // التحقق من التوقيع إذا كان Lean يوفره
        $signature = $request->header('X-Lean-Signature');
        if ($signature) {
            $payload = $request->getContent();
            $computedSignature = hash_hmac('sha256', $payload, $this->leanWebhookSecret);
            if (!hash_equals($computedSignature, $signature)) {
                Log::error('Invalid Lean webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        } else {
            Log::warning('Lean webhook missing signature header');
            // قد نقرر قبول الطلبات بدون توقيع في بيئة التطوير فقط
            if (app()->environment('production')) {
                return response()->json(['error' => 'Missing signature'], 401);
            }
        }

        $data = $request->all();
        $eventType = $data['type'] ?? $data['event'] ?? 'unknown';

        switch ($eventType) {
            case 'transfer.updated':
            case 'transfer.status_changed':
                return $this->handleLeanTransferUpdated($data);
            case 'account.connected':
                return $this->handleLeanAccountConnected($data);
            case 'account.disconnected':
                return $this->handleLeanAccountDisconnected($data);
            case 'transfer.failed':
                return $this->handleLeanTransferFailed($data);
            default:
                Log::info('Unhandled Lean webhook event', ['event' => $eventType]);
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * معالجة تحديث حالة تحويل من Lean
     * 
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleLeanTransferUpdated(array $data)
    {
        // معرف التحويل في نظام Lean
        $transferId = $data['transfer_id'] ?? $data['id'] ?? null;
        if (!$transferId) {
            Log::error('Lean transfer updated webhook missing transfer_id');
            return response()->json(['error' => 'Missing transfer_id'], 400);
        }

        // البحث عن الاستقطاع المرتبط بهذا transferId
        // نفترض أننا خزنا معرف Lean في حقل transaction_reference بجدول deductions
        $deduction = Deduction::where('transaction_reference', $transferId)->first();

        if (!$deduction) {
            Log::warning('Deduction not found for Lean transfer', ['transfer_id' => $transferId]);
            return response()->json(['status' => 'not_found']);
        }

        // تحديث الحالة بناءً على حالة التحويل من Lean
        $leanStatus = $data['status'] ?? 'unknown';
        switch ($leanStatus) {
            case 'completed':
            case 'success':
                $deduction->status = 'success';
                $deduction->processed_date = now();
                break;
            case 'failed':
                $deduction->status = 'failed';
                $deduction->failure_reason = $data['failure_reason'] ?? 'فشل في التحويل البنكي';
                break;
            case 'pending':
            case 'processing':
                $deduction->status = 'processing';
                break;
            default:
                Log::info('Unhandled Lean transfer status', ['status' => $leanStatus]);
                // لا تغيير
                break;
        }

        $deduction->save();

        // إذا كان الاستقطاع ناجحاً، قد نحتاج لتحديث بيانات العقد (المبلغ المدفوع)
        if ($deduction->status === 'success' && $deduction->contract_id) {
            $this->updateContractAfterSuccessfulDeduction($deduction);
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * معالجة فشل تحويل من Lean (قد يكون نفس transfer.updated لكن نضعه للتوضيح)
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleLeanTransferFailed(array $data)
    {
        // مشابه للتحديث
        return $this->handleLeanTransferUpdated($data);
    }

    /**
     * معالجة ربط حساب جديد من Lean
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleLeanAccountConnected(array $data)
    {
        // بيانات الحساب المرتبط
        $accountData = $data['account'] ?? $data;
        $providerAccountId = $accountData['id'] ?? null;
        $iban = $accountData['iban'] ?? null;
        $bankName = $accountData['bank']['name'] ?? 'غير معروف';
        $accountName = $accountData['name'] ?? 'حساب بنكي';
        $customerId = $accountData['customer_id'] ?? $data['customer_id'] ?? null; // نحتاج لمعرف العميل لدينا

        if (!$providerAccountId || !$customerId) {
            Log::error('Lean account connected missing required fields', ['data' => $data]);
            return response()->json(['error' => 'Missing fields'], 400);
        }

        // البحث عن العميل في نظامنا (قد نحتاج لربطه بطريقة أخرى)
        // نفترض أن Lean يرسل لنا معرف العميل الخاص بنا (مثلاً national_id أو user_id)
        $user = User::where('national_id', $customerId)->orWhere('id', $customerId)->first();
        if (!$user) {
            Log::error('User not found for Lean account connected', ['customer_id' => $customerId]);
            return response()->json(['error' => 'User not found'], 404);
        }

        // إنشاء أو تحديث حساب العميل
        CustomerAccount::updateOrCreate(
            ['provider_account_id' => $providerAccountId],
            [
                'customer_id' => $user->id,
                'iban' => $iban,
                'bank_name' => $bankName,
                'account_name' => $accountName,
                'current_balance' => $accountData['balance'] ?? 0,
                'currency' => $accountData['currency'] ?? 'SAR',
            ]
        );

        return response()->json(['status' => 'created']);
    }

    /**
     * معالجة إلغاء ربط حساب من Lean
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleLeanAccountDisconnected(array $data)
    {
        $providerAccountId = $data['account_id'] ?? $data['id'] ?? null;
        if (!$providerAccountId) {
            return response()->json(['error' => 'Missing account_id'], 400);
        }

        // يمكننا إما حذف الحساب أو تعطيله
        CustomerAccount::where('provider_account_id', $providerAccountId)->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * تحديث بيانات العقد بعد استقطاع ناجح
     *
     * @param Deduction $deduction
     * @return void
     */
    protected function updateContractAfterSuccessfulDeduction(Deduction $deduction)
    {
        $contract = $deduction->contract;
        if (!$contract) return;

        // تحديث المبالغ المدفوعة
        $contract->paid_amount += $deduction->amount;
        $contract->remaining_amount = $contract->total_amount - $contract->paid_amount;

        // إذا كان العقد يحتوي على أقساط شهرية
        if ($contract->monthly_installment > 0 && $deduction->amount >= $contract->monthly_installment) {
            $contract->paid_installments += 1;
            $contract->remaining_installments = $contract->installment_count - $contract->paid_installments;
        }

        // إذا تم سداد كامل المبلغ
        if ($contract->remaining_amount <= 0) {
            $contract->status = 'closed';
            $contract->remaining_amount = 0;
        }

        $contract->save();
    }

    /**
     * معالجة Webhooks القادمة من سداد (Sadad)
     * 
     * الأحداث المحتملة:
     * - bill.paid (تم دفع فاتورة)
     * - bill.created (فاتورة جديدة)
     * - bill.updated (تحديث فاتورة)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSadadWebhook(Request $request)
    {
        // التحقق من التوقيع (مشابه لـ Lean)
        $signature = $request->header('X-Sadad-Signature');
        if ($signature) {
            $payload = $request->getContent();
            $computedSignature = hash_hmac('sha256', $payload, $this->sadadWebhookSecret);
            if (!hash_equals($computedSignature, $signature)) {
                Log::error('Invalid Sadad webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        } else {
            Log::warning('Sadad webhook missing signature header');
            if (app()->environment('production')) {
                return response()->json(['error' => 'Missing signature'], 401);
            }
        }

        $data = $request->all();
        $eventType = $data['event'] ?? $data['type'] ?? 'unknown';

        switch ($eventType) {
            case 'bill.paid':
                return $this->handleSadadBillPaid($data);
            case 'bill.created':
                return $this->handleSadadBillCreated($data);
            case 'bill.updated':
                return $this->handleSadadBillUpdated($data);
            default:
                Log::info('Unhandled Sadad webhook event', ['event' => $eventType]);
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * معالجة إشعار دفع فاتورة من سداد
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSadadBillPaid(array $data)
    {
        // منطق معالجة دفع فاتورة
        // يمكن البحث عن عقد مرتبط برقم الفاتورة وتحديث حالته
        $billNumber = $data['bill_number'] ?? null;
        $amount = $data['amount'] ?? 0;
        $customerId = $data['customer_id'] ?? null;

        if (!$billNumber) {
            return response()->json(['error' => 'Missing bill_number'], 400);
        }

        // البحث عن العقد المرتبط برقم الفاتورة (قد يكون contract_number)
        $contract = Contract::where('contract_number', $billNumber)->first();
        if ($contract) {
            // إنشاء استقطاع ناجح (إذا لم يكن موجوداً)
            $deduction = Deduction::where('contract_id', $contract->id)
                ->where('transaction_reference', $billNumber)
                ->first();

            if (!$deduction) {
                // إنشاء سجل استقطاع جديد
                $deduction = Deduction::create([
                    'customer_id' => $contract->customer_id,
                    'org_id' => $contract->org_id,
                    'contract_id' => $contract->id,
                    'amount' => $amount,
                    'platform_fee' => 0, // قد تكون العمولة محسوبة في مكان آخر
                    'source_account_id' => null, // قد لا نعرف المصدر
                    'target_type' => 'sadad_biller',
                    'status' => 'success',
                    'scheduled_date' => now(),
                    'processed_date' => now(),
                    'transaction_reference' => $billNumber,
                ]);
            } else {
                $deduction->status = 'success';
                $deduction->processed_date = now();
                $deduction->save();
            }

            // تحديث العقد
            $this->updateContractAfterSuccessfulDeduction($deduction);
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * معالجة إنشاء فاتورة جديدة من سداد
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSadadBillCreated(array $data)
    {
        // يمكن استخدام هذا الإشعار لإنشاء عقد جديد تلقائي لفواتير الخدمات
        // إذا كان العميل لديه موافقة عامة (consent) على سداد الفواتير
        $billNumber = $data['bill_number'] ?? null;
        $amount = $data['amount'] ?? 0;
        $customerNationalId = $data['customer_national_id'] ?? null;
        $billerCode = $data['biller_code'] ?? null; // مثلاً 001 للكهرباء

        if (!$customerNationalId || !$billNumber) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        // البحث عن العميل
        $customer = User::where('national_id', $customerNationalId)->where('type', 'customer')->first();
        if (!$customer) {
            Log::warning('Customer not found for Sadad bill', ['national_id' => $customerNationalId]);
            return response()->json(['status' => 'customer_not_found']);
        }

        // البحث عن الموافقة العامة للعميل على سداد الفواتير
        $hasConsent = $customer->consents()
            ->where('consent_type', 'general_billing')
            ->where('status', 'approved')
            ->exists();

        if (!$hasConsent) {
            Log::info('No general billing consent for customer', ['customer_id' => $customer->id]);
            return response()->json(['status' => 'no_consent']);
        }

        // البحث عن الجهة (المنشأة) المسؤولة عن هذا البيلر
        // قد نخزن في جدول organization حقل biller_code
        $organization = Organization::where('cr_number', $billerCode)->orWhere('name', 'like', '%' . $billerCode . '%')->first();
        if (!$organization) {
            // قد ننشئ منشأة افتراضية للجهة
            Log::warning('Organization not found for biller', ['biller_code' => $billerCode]);
            return response()->json(['status' => 'organization_not_found']);
        }

        // إنشاء عقد جديد للفاتورة
        $contract = Contract::create([
            'org_id' => $organization->id,
            'customer_id' => $customer->id,
            'contract_number' => $billNumber,
            'contract_type' => 'utility_bill',
            'total_amount' => $amount,
            'remaining_amount' => $amount,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active', // لأن الفواتير عادة تحتاج دفع فوري، لكننا نضعها active
        ]);

        // إنشاء استقطاع فوري (لليوم)
        Deduction::create([
            'customer_id' => $customer->id,
            'org_id' => $organization->id,
            'contract_id' => $contract->id,
            'amount' => $amount,
            'platform_fee' => 0, // يمكن حسابها لاحقاً
            'source_account_id' => $customer->primaryAccount?->id, // قد نحتاج لاختيار الحساب المناسب
            'target_type' => 'sadad_biller',
            'status' => 'pending',
            'scheduled_date' => now(),
        ]);

        return response()->json(['status' => 'contract_created']);
    }

    /**
     * معالجة تحديث فاتورة من سداد
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSadadBillUpdated(array $data)
    {
        // يمكن تحديث مبلغ العقد إذا تغيرت الفاتورة
        $billNumber = $data['bill_number'] ?? null;
        $newAmount = $data['amount'] ?? null;

        if (!$billNumber || !$newAmount) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $contract = Contract::where('contract_number', $billNumber)->first();
        if ($contract && $contract->status == 'active') {
            $contract->total_amount = $newAmount;
            $contract->remaining_amount = $newAmount - $contract->paid_amount;
            $contract->save();
        }

        return response()->json(['status' => 'updated']);
    }
}
