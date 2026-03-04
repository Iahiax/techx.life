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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['customer', 'org_user', 'admin'])->default('customer');
            $table->string('national_id')->unique()->nullable();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('auth_provider')->nullable(); // nafath, tawtheeq
            $table->string('provider_id')->nullable(); // معرف من المزود
            $table->boolean('is_active')->default(true);
            $table->string('password')->nullable(); // قد يستخدم للمشرفين أو تسجيل الدخول اليدوي
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // إضافة حذف ناعم اختياري
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
