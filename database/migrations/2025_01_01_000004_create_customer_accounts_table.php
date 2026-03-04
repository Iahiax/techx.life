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
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('provider_account_id')->unique()->comment('معرف الحساب من مزود Open Banking (Lean)');
            $table->string('iban')->nullable();
            $table->string('bank_name');
            $table->string('account_name');
            $table->boolean('is_salary_account')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('currency')->default('SAR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};
