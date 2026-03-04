Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('org_id')->constrained()->onDelete('cascade');
    $table->string('package'); // small, medium, large, government
    $table->decimal('price', 10, 2);
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['active', 'expired', 'canceled'])->default('active');
    $table->timestamps();
});
