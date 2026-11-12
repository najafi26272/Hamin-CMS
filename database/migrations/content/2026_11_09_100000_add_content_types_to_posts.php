<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // نوع محتوا: news (اخبار)، visual (لوح)، audio (شنیداری)، video (تماشایی)، poem (شعر)
            $table->string('type', 20)->default('news')->index()->after('user_id');
            $table->string('creator_name')->nullable()->after('slug');   // نام صاحب اثر / سازنده / شاعر
            $table->string('media_file')->nullable()->after('image');    // فایل اصلی: تصویر، صوت یا ویدیو

            // وضعیت: draft | pending | published | rejected
            $table->string('status', 20)->default('draft')->change();
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();

            // متن برای «لوح»، «شنیداری» و «تماشایی» اختیاری است
            $table->longText('content')->nullable()->change();
        });

        // هر تخصص هنری به یک نوع محتوا مربوط است
        Schema::table('art_specialties', function (Blueprint $table) {
            $table->string('content_type', 20)->nullable()->after('name');
        });

        $map = [
            'شاعر' => 'poem',
            'عکاس' => 'visual',
            'گرافیست' => 'visual',
            'فیلم ساز' => 'video',
            'آهنگساز' => 'audio',
            'خواننده' => 'audio',
        ];

        foreach ($map as $name => $type) {
            DB::table('art_specialties')->updateOrInsert(['name' => $name], ['content_type' => $type, 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('art_specialties', function (Blueprint $table) {
            $table->dropColumn('content_type');
        });

        DB::table('posts')->whereIn('status', ['pending', 'rejected'])->update(['status' => 'draft']);

        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['type', 'creator_name', 'media_file', 'rejection_reason', 'reviewed_at']);
            $table->enum('status', ['draft', 'published'])->default('draft')->change();
        });
    }
};
