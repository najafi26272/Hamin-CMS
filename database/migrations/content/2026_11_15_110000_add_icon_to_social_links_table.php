<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// آیکن دلخواه (تصویر) برای هر شبکه اجتماعی؛ اگر خالی باشد آیکن پیش‌فرض سایت نمایش داده می‌شود
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
