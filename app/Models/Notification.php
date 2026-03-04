Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('body');
    $table->string('type'); // contract_approval, deduction_notice, etc.
    $table->foreignId('contract_id')->nullable()->constrained();
    $table->boolean('is_read')->default(false);
    $table->timestamps();
});
