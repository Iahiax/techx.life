Schema::create('customer_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->string('provider_account_id'); // من Lean
    $table->string('iban');
    $table->string('bank_name');
    $table->string('account_name');
    $table->boolean('is_salary_account')->default(false);
    $table->boolean('is_primary')->default(false);
    $table->decimal('current_balance', 15, 2)->default(0);
    $table->string('currency')->default('SAR');
    $table->timestamps();
});
