Schema::create('contracts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('org_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->string('contract_number')->unique();
    $table->enum('contract_type', ['funding', 'leasing', 'government_fee', 'utility_bill', 'subscription', 'personal_loan', 'other']);
    $table->decimal('principal_amount', 15, 2)->nullable(); // أصل المبلغ
    $table->decimal('total_amount', 15, 2); // إجمالي المبلغ المطلوب
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->decimal('remaining_amount', 15, 2);
    $table->decimal('monthly_installment', 15, 2)->nullable();
    $table->integer('installment_count')->nullable();
    $table->integer('paid_installments')->default(0);
    $table->integer('remaining_installments')->nullable();
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->enum('status', ['pending_approval', 'active', 'rejected', 'closed', 'defaulted'])->default('pending_approval');
    $table->json('raw_contract_data')->nullable(); // تفاصيل إضافية
    $table->timestamps();
});
