Schema::create('org_users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('org_id')->constrained()->onDelete('cascade');
    $table->string('role')->default('OrgUser'); // OrgAdmin, OrgUser
    $table->timestamps();
});
