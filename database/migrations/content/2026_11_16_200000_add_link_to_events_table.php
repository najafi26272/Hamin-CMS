<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// لینک شرکت در رویداد (ثبت‌نام، جلسه آنلاین، فرم و ...) و متن دلخواه دکمه
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('link', 500)->nullable()->after('poster');
            $table->string('link_label', 50)->nullable()->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['link', 'link_label']);
        });
    }
};
