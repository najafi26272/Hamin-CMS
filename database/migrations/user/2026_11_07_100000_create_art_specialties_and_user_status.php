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
        // تخصص‌های هنری
        Schema::create('art_specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // هر کاربر می‌تواند چند تخصص داشته باشد
        Schema::create('art_specialty_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('art_specialty_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'art_specialty_id']);
        });

        $defaults = ['شاعر', 'عکاس', 'گرافیست', 'فیلم ساز', 'آهنگساز', 'خواننده'];
        foreach ($defaults as $name) {
            DB::table('art_specialties')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        // انتقال تخصص‌های قبلی کاربران (ستون JSON) به جدول جدید
        foreach (DB::table('users')->whereNotNull('art_specialty')->get(['id', 'art_specialty']) as $user) {
            foreach ((array) json_decode($user->art_specialty, true) as $name) {
                $specialtyId = DB::table('art_specialties')->where('name', $name)->value('id')
                    ?? DB::table('art_specialties')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);

                DB::table('art_specialty_user')->insertOrIgnore(['user_id' => $user->id, 'art_specialty_id' => $specialtyId]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('art_specialty');

            // وضعیت حساب: کاربران فعلی تأیید شده‌اند، هنرمندان ثبت‌نامی در انتظار تأیید
            $table->string('status', 20)->default('approved')->index()->after('password');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'rejection_reason', 'reviewed_at']);
            $table->text('art_specialty')->nullable();
        });

        Schema::dropIfExists('art_specialty_user');
        Schema::dropIfExists('art_specialties');
    }
};
