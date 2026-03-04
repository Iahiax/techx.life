// database/migrations/2025_01_01_000001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->enum('type', ['customer', 'org_user', 'admin'])->default('customer');
    $table->string('national_id')->unique()->nullable();
    $table->string('full_name');
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('auth_provider')->nullable(); // nafath, tawtheeq
    $table->string('provider_id')->nullable(); // معرف من المزود
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password')->nullable(); // قد لا نحتاج لكلمة مرور
    $table->rememberToken();
    $table->timestamps();
});
