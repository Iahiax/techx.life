Schema::create('consents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('org_id')->constrained()->onDelete('cascade');
    $table->foreignId('contract_id')->constrained()->onDelete('cascade');
    $table->enum('consent_type', ['salary_deduction', 'account_deduction', 'general_billing']);
    $table->enum('status', ['pending', 'approved', 'rejected', 'revoked'])->default('pending');
    $table->timestamps();
});
