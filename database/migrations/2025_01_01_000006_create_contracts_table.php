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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            
            // المفاتيح الخارجية
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            
            // معلومات العقد
            $table->string('contract_number')->unique();
            $table->enum('contract_type', [
                'funding',
                'leasing',
                'government_fee',
                'utility_bill',
                'subscription',
                'personal_loan',
                'other'
            ]);
            
            // المبالغ المالية
            $table->decimal('principal_amount', 15, 2)->nullable()->comment('أصل المبلغ إن وجد');
            $table->decimal('total_amount', 15, 2)->comment('إجمالي المبلغ المطلوب');
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            
            // معلومات الأقساط الشهرية
            $table->decimal('monthly_installment', 15, 2)->nullable();
            $table->integer('installment_count')->nullable();
            $table->integer('paid_installments')->default(0);
            $table->integer('remaining_installments')->nullable();
            
            // التواريخ
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // الحالة
            $table->enum('status', [
                'pending_approval',
                'active',
                'rejected',
                'closed',
                'defaulted'
            ])->default('pending_approval');
            
            // بيانات إضافية بصيغة JSON
            $table->json('raw_contract_data')->nullable()->comment('تفاصيل العقد الكاملة كما أرسلها الطرف');
            
            $table->timestamps();
            $table->softDeletes();
            
            // الفهارس لتحسين الأداء
            $table->index('status');
            $table->index('contract_number');
            $table->index('customer_id');
            $table->index('org_id');
            $table->index(['customer_id', 'status']);
            $table->index(['org_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
