<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ثبت‌نام عمومی شد: هر کسی با نام، نام خانوادگی، نام کاربری و رمز ثبت‌نام می‌کند و بلافاصله وارد پنل می‌شود.
 * ستون status از «وضعیت حساب» به «وضعیت درخواست هنرمندی» (artist_status) تغییر می‌کند:
 * null = کاربر عادی (درخواستی نداده)، pending / approved / rejected = وضعیت درخواست هنرمندی.
 * موبایل و ایمیل در ثبت‌نام عمومی گرفته نمی‌شوند و اختیاری می‌شوند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('status', 'artist_status');
            $table->renameColumn('rejection_reason', 'artist_rejection_reason');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('artist_status', 20)->nullable()->default(null)->change();
            $table->string('mobile', 20)->nullable()->change();
            $table->string('email')->nullable()->change();
        });

        $artistRoleId = DB::table('roles')->where('name', 'artist')->value('id');
        $artistIds = $artistRoleId
            ? DB::table('role_user')->where('role_id', $artistRoleId)->pluck('user_id')
            : collect();

        // کاربرانی که قبلا هنرمند تأییدشده بودند همچنان هنرمند هستند؛ بقیه کاربر عادی
        DB::table('users')->where('artist_status', 'approved')->whereNotIn('id', $artistIds)->update(['artist_status' => null]);

        // درخواست‌های در انتظار یا ردشده تا تأیید مدیر نقش هنرمند ندارند
        if ($artistRoleId) {
            $notApproved = DB::table('users')->whereIn('artist_status', ['pending', 'rejected'])->pluck('id');
            DB::table('role_user')->where('role_id', $artistRoleId)->whereIn('user_id', $notApproved)->delete();
        }
    }

    public function down(): void
    {
        DB::table('users')->whereNull('artist_status')->update(['artist_status' => 'approved']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('artist_status', 20)->default('approved')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('artist_status', 'status');
            $table->renameColumn('artist_rejection_reason', 'rejection_reason');
        });
    }
};
