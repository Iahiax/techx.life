<?php

namespace App\Console\Commands;

use App\Models\Deduction;
use App\Services\DeductionEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * أمر تنفيذ الاستقطاعات المجدولة
 * 
 * يتم تشغيل هذا الأمر تلقائياً يومياً عبر جدولة Laravel.
 * يقوم بجلب جميع الاستقطاعات المقررة لهذا اليوم والتي بحالة pending،
 * ثم يقوم بمعالجتها واحداً تلو الآخر عبر DeductionEngine.
 */
class ProcessDeductions extends Command
{
    /**
     * اسم وسيط الأمر (الذي يُكتب في سطر الأوامر)
     *
     * @var string
     */
    protected $signature = 'deductions:process';

    /**
     * وصف الأمر
     *
     * @var string
     */
    protected $description = 'معالجة جميع الاستقطاعات المجدولة لليوم الحالي';

    /**
     * محرك معالجة الاستقطاعات (حقن التبعية)
     *
     * @var \App\Services\DeductionEngine
     */
    protected DeductionEngine $deductionEngine;

    /**
     * إنشاء مثيل جديد للأمر
     *
     * @param DeductionEngine $deductionEngine
     * @return void
     */
    public function __construct(DeductionEngine $deductionEngine)
    {
        parent::__construct();
        $this->deductionEngine = $deductionEngine;
    }

    /**
     * تنفيذ الأمر
     *
     * @return int
     */
    public function handle()
    {
        $today = now()->toDateString();
        
        $this->info("بدء معالجة الاستقطاعات ليوم {$today}");

        // جلب الاستقطاعات المعلقة لهذا اليوم
        $deductions = Deduction::query()
            ->where('scheduled_date', $today)
            ->where('status', 'pending')
            ->with(['customer', 'organization', 'contract', 'sourceAccount'])
            ->get();

        $count = $deductions->count();
        $this->info("عدد الاستقطاعات المعلقة: {$count}");

        if ($count === 0) {
            $this->info('لا توجد استقطاعات لليوم.');
            return Command::SUCCESS;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($deductions as $deduction) {
            try {
                $this->line("معالجة الاستقطاع رقم {$deduction->id} ...");

                // استخدام المحرك لتنفيذ الاستقطاع
                $result = $this->deductionEngine->process($deduction);

                if ($result['success']) {
                    $successCount++;
                    $this->info("✓ تم بنجاح. مرجع العملية: {$result['reference']}");
                } else {
                    $failCount++;
                    $this->error("✗ فشل. السبب: {$result['reason']}");
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error('استثناء أثناء معالجة الاستقطاع', [
                    'deduction_id' => $deduction->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("✗ خطأ غير متوقع: {$e->getMessage()}");
            }
        }

        $this->info("انتهت المعالجة. نجاح: {$successCount}, فشل: {$failCount}");

        return Command::SUCCESS;
    }
}
