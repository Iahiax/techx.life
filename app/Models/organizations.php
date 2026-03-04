Schema::create('organizations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('cr_number')->unique(); // سجل تجاري أو رقم الجهة
    $table->enum('type', ['funding', 'leasing', 'government', 'billing', 'telecom', 'utility', 'individual_beneficiary', 'other']);
    $table->boolean('is_trusted')->default(false); // للجهات الموثوقة (سداد، كهرباء)
    $table->string('subscription_package')->nullable(); // small, medium, large, government
    $table->timestamps();
});
