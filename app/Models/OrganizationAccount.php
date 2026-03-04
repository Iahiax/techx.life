Schema::create('organization_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('org_id')->constrained()->onDelete('cascade');
    $table->string('iban');
    $table->string('bank_name');
    $table->string('account_name');
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});
