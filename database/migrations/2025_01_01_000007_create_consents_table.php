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
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            
            // المفاتيح الخارجية
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            
            // نوع الموافقة
            $table->enum('consent_type', [
                'salary_deduction',
                'account_deduction',
                'general_billing'
            ]);
            
            // حالة الموافقة
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'revoked'
            ])->default('pending');
            
            $table->timestamps();
            
            // منع تكرار نفس الموافقة لنفس العقد والعميل
            $table->unique(['customer_id', 'contract_id'], 'unique_customer_contract_consent');
            
            // الفهارس لتحسين الأداء
            $table->index('status');
            $table->index('consent_type');
            $table->index('customer_id');
            $table->index('org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
