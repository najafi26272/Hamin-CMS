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
        // تخصص هنری چند انتخابی است و به صورت JSON ذخیره می‌شود
        Schema::table('users', function (Blueprint $table) {
            $table->text('art_specialty')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('art_specialty')->nullable()->change();
        });
    }
};
