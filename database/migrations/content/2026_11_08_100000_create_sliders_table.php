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
        // اسلایدرهای صفحه اول سایت
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image');                              // فایل بنر
            $table->string('link', 500)->nullable();              // آدرس کامل یا مسیر داخلی سایت
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);    // ترتیب نمایش (کوچک‌تر = جلوتر)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
