<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// لینک شبکه‌های اجتماعی سایت (هر شبکه یک ردیف ثابت؛ مدیر فقط لینک، وضعیت و ترتیب را تغییر می‌دهد)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 30)->unique(); // کلید در SocialLink::PLATFORMS
            $table->string('url', 500)->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $platforms = ['eitaa', 'telegram', 'instagram', 'whatsapp', 'aparat', 'youtube'];
        foreach ($platforms as $i => $platform) {
            DB::table('social_links')->insert([
                'platform' => $platform,
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
