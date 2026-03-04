<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deductions', function (Blueprint $table) {
            $table->id();
            
            // المفاتيح الخارجية
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');
            
            // المبالغ
            $table->decimal('amount', 15, 2)->comment('المبلغ المحسوم (بعد خصم عمولة المنصة)');
            $table->decimal('platform_fee', 15, 2)->default(0)->comment('عمولة المنصة 1%');
            
            // الحسابات المصدر والهدف
            $table->foreignId('source_account_id')->nullable()->constrained('customer_accounts')->onDelete('set null');
            $table->foreignId('target_account_id')->nullable()->constrained('organization_accounts')->onDelete('set null');
            $table->string('target_type')->default('bank_account')->comment('نوع الهدف: bank_account, sadad_biller, wallet, individual_account');
            
            // حالة الاستقطاع
            $table->enum('status', [
                'pending',
                'processing',
                'success',
                'failed'
            ])->default('pending');
            
            // التواريخ
            $table->date('scheduled_date')->comment('تاريخ الاستقطاع المقرر');
            $table->date('processed_date')->nullable()->comment('تاريخ التنفيذ الفعلي');
            
            // مراجع وتفاصيل إضافية
            $table->string('transaction_reference')->nullable()->comment('مرجع العملية من البنك أو Lean');
            $table->text('failure_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // الفهارس لتحسين الأداء
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('transaction_reference');
            $table->index('customer_id');
            $table->index('org_id');
            $table->index('contract_id');
            $table->index(['scheduled_date', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deductions');
    }
};
