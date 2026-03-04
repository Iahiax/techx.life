Schema::create('deductions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('org_id')->constrained()->onDelete('cascade');
    $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
    $table->decimal('amount', 15, 2); // المبلغ المحسوم (بعد خصم عمولة المنصة)
    $table->decimal('platform_fee', 15, 2)->default(0); // عمولة المنصة 1%
    $table->foreignId('source_account_id')->constrained('customer_accounts');
    $table->foreignId('target_account_id')->nullable()->constrained('organization_accounts');
    $table->string('target_type')->default('bank_account'); // sadad_biller, wallet, etc.
    $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
    $table->date('scheduled_date');
    $table->date('processed_date')->nullable();
    $table->string('transaction_reference')->nullable();
    $table->string('failure_reason')->nullable();
    $table->timestamps();
});
