<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_builder_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('requires_auth')->default(false);  // فقط کاربران واردشده
            $table->boolean('allow_multiple')->default(true);  // هر کاربر چند بار بتواند ثبت کند
            $table->string('submit_label')->nullable();
            $table->text('success_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('form_builder_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('form_builder_forms')->cascadeOnDelete();
            $table->string('kind', 20)->default('input');       // input: ورودی خام | profile: از پروفایل کاربر
            $table->string('type', 30);                         // نوع ورودی یا کلید فیلد پروفایل
            $table->string('name', 60);                         // کلید یکتا در فرم (در داده ثبت‌شده)
            $table->string('label');
            $table->string('placeholder')->nullable();
            $table->string('help')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_readonly')->default(true);      // فقط برای فیلدهای پروفایل
            $table->string('width', 10)->default('full');       // full | half
            $table->json('options')->nullable();                // گزینه‌های لیست، رادیو و چک‌باکس
            $table->json('settings')->nullable();               // تنظیمات خاص هر نوع
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'name']);
        });

        Schema::create('form_builder_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('form_builder_forms')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('data');      // name => value (فایل‌ها: آرایه‌ای از {path, name, size})
            $table->json('labels');    // name => عنوان فیلد در زمان ثبت (اگر فیلد بعدا عوض شود)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_builder_submissions');
        Schema::dropIfExists('form_builder_fields');
        Schema::dropIfExists('form_builder_forms');
    }
};
