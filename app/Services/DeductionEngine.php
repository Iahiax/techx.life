<?php

namespace App\Services;

use App\Models\Deduction;
use App\Models\CustomerAccount;
use App\Models\Contract;
use App\Services\OpenBanking\LeanService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * محرك تنفيذ الاستقطاعات
 * 
 * يقوم هذا المحرك بتنفيذ عملية الاستقطاع الفعلية:
 * - اختيار الحساب المناسب للخصم
 * - حساب عمولة المنصة (1%)
 * - استدعاء Lean لإنشاء التحويل
 * - تحديث حالة الاستقطاع والعقد
 */
class DeductionEngine
{
    /**
     * خدمة التكامل مع Lean
     *
     * @var \App\Services\OpenBanking\LeanService
     */
    protected $leanService;

    /**
     * خدمة حساب العمولة
     *
     * @var \App\Services\FeeCalculator
     */
    protected $feeCalculator;

    /**
     * إنشاء مثيل جديد للمحرك
     *
     * @param LeanService $leanService
     * @param FeeCalculator $feeCalculator
     */
    public function __construct(LeanService $leanService, FeeCalculator $feeCalculator)
    {
        $this->leanService = $leanService;
        $this->feeCalculator = $feeCalculator;
    }

    /**
     * تنفيذ استقطاع معين
     *
     * @param Deduction $deduction
     * @return array
     */
    public function process(Deduction $deduction): array
    {
        // تغيير الحالة إلى processing
        $deduction->updateStatus('processing');

        try {
            // 1. التحقق من وجود حساب مصدر (إذا لم يكن محدداً، نختار واحداً)
            if (!$deduction->source_account_id) {
                $account = $this->chooseSourceAccount($deduction->customer);
                if (!$account) {
                    throw new Exception('لا يوجد حساب بنكي مرتبط للعميل يمكن استخدامه للاستقطاع.');
                }
                $deduction->source_account_id = $account->id;
                $deduction->save();
            } else {
                $account = $deduction->sourceAccount;
            }

            // 2. حساب العمولة إذا لم تكن محسوبة مسبقاً
            if ($deduction->platform_fee == 0) {
                $deduction->platform_fee = $this->feeCalculator->calculate($deduction->amount);
                $deduction->save();
            }

            // 3. تنفيذ التحويل عبر Lean (نحتاج access token للعميل)
            //    نفترض أن LeanService يتعامل مع token، أو أننا نخزن token في جدول customer_accounts
            //    للتبسيط، نفترض أن LeanService يستخدم access token مخزن في الجلسة أو نطلب من العميل إعادة المصادقة.
            //    هنا سنفترض أن leanService يمكنه إنشاء تحويل باستخدام account provider id.
            $transferData = $this->leanService->createTransfer(
                $this->getCustomerAccessToken($deduction->customer),
                $account->provider_account_id,
                $this->getTargetIban($deduction),
                $deduction->amount,
                'استقطاع شهري بموجب عقد رقم ' . ($deduction->contract->contract_number ?? 'غير معروف')
            );

            // 4. تحديث سجل الاستقطاع
            $deduction->transaction_reference = $transferData['id'] ?? null;
            $deduction->status = 'success';
            $deduction->processed_date = now();
            $deduction->save();

            // 5. تحديث العقد المرتبط (إذا وجد)
            if ($deduction->contract_id) {
                $this->updateContract($deduction);
            }

            Log::info('Deduction processed successfully', [
                'deduction_id' => $deduction->id,
                'reference' => $deduction->transaction_reference
            ]);

            return [
                'success' => true,
                'reference' => $deduction->transaction_reference,
                'message' => 'تم الاستقطاع بنجاح'
            ];

        } catch (Exception $e) {
            // تسجيل الفشل
            $deduction->status = 'failed';
            $deduction->failure_reason = $e->getMessage();
            $deduction->save();

            Log::error('Deduction failed', [
                'deduction_id' => $deduction->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'reason' => $e->getMessage()
            ];
        }
    }

    /**
     * اختيار الحساب المناسب للاستقطاع من قائمة حسابات العميل
     *
     * @param \App\Models\User $customer
     * @return \App\Models\CustomerAccount|null
     */
    protected function chooseSourceAccount($customer)
    {
        // 1. نفضل حساب الراتب
        $account = $customer->bankAccounts()->salaryAccounts()->first();
        if ($account) {
            return $account;
        }

        // 2. ثم الحساب الأساسي
        $account = $customer->bankAccounts()->primary()->first();
        if ($account) {
            return $account;
        }

        // 3. ثم أي حساب آخر (الأول)
        return $customer->bankAccounts()->first();
    }

    /**
     * الحصول على IBAN المستهدف (حساب الجهة)
     *
     * @param Deduction $deduction
     * @return string
     * @throws Exception
     */
    protected function getTargetIban(Deduction $deduction): string
    {
        // إذا كان هناك حساب هدف محدد، نستخدم IBAN الخاص به
        if ($deduction->target_account_id) {
            $targetAccount = $deduction->targetAccount;
            if ($targetAccount && $targetAccount->iban) {
                return $targetAccount->iban;
            }
        }

        // وإلا نبحث عن الحساب الافتراضي للجهة
        if ($deduction->organization) {
            $defaultAccount = $deduction->organization->accounts()->default()->first();
            if ($defaultAccount && $defaultAccount->iban) {
                return $defaultAccount->iban;
            }
        }

        throw new Exception('لا يوجد حساب بنكي مستهدف للجهة.');
    }

    /**
     * الحصول على access token الخاص بالعميل من Lean
     * (هذا افتراضي - في الواقع يجب تخزين token في جدول customer_accounts أو ما يشابه)
     *
     * @param \App\Models\User $customer
     * @return string
     * @throws Exception
     */
    protected function getCustomerAccessToken($customer)
    {
        // نفترض أن access token مخزن في علاقة مع الحسابات
        // أو نستخدم refresh token. هنا نرمي خطأ للإشارة إلى أننا بحاجة لتطبيق حقيقي.
        // في مشروع حقيقي، قد تخزن التوكنات في جدول منفصل أو في customer_accounts.
        throw new Exception('لم يتم تنفيذ آلية الحصول على access token بعد. يجب تخزين token في قاعدة البيانات.');
    }

    /**
     * تحديث بيانات العقد بعد استقطاع ناجح
     *
     * @param Deduction $deduction
     * @return void
     */
    protected function updateContract(Deduction $deduction)
    {
        $contract = $deduction->contract;
        if (!$contract) return;

        // تحديث المبالغ
        $contract->paid_amount += $deduction->amount;
        $contract->remaining_amount = $contract->total_amount - $contract->paid_amount;

        // تحديث عدد الأقساط المدفوعة إذا كان العقد ذا أقساط شهرية
        if ($contract->monthly_installment > 0 && $deduction->amount >= $contract->monthly_installment) {
            $contract->paid_installments += 1;
            $contract->remaining_installments = $contract->installment_count - $contract->paid_installments;
        }

        // إذا تم السداد الكامل
        if ($contract->remaining_amount <= 0) {
            $contract->status = 'closed';
            $contract->remaining_amount = 0;
        }

        $contract->save();
    }
}
