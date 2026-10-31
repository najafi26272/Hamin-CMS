<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('avatar')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->unique();
            $table->string('city')->nullable();
            $table->string('virtual_space_number', 20)->nullable(); // شماره فضای مجازی
            $table->string('instagram_id')->nullable();           // بدون @
            $table->string('education_degree')->nullable();
            $table->string('art_specialty')->nullable();          // تخصص هنری
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
