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
        Schema::create('organization_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->string('iban');
            $table->string('bank_name');
            $table->string('account_name');
            $table->boolean('is_default')->default(false)->comment('الحساب الافتراضي لاستقبال الأموال');
            $table->timestamps();

            // إضافة فهرس لسرعة البحث
            $table->index('org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_accounts');
    }
};
