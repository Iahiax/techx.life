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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cr_number')->unique()->comment('رقم السجل التجاري أو رقم الجهة');
            $table->enum('type', [
                'funding',
                'leasing',
                'government',
                'billing',
                'telecom',
                'utility',
                'individual_beneficiary',
                'other'
            ])->default('other');
            $table->boolean('is_trusted')->default(false)->comment('للجهات الموثوقة مثل سداد، الكهرباء');
            $table->enum('subscription_package', ['small', 'medium', 'large', 'government'])->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
