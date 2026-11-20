<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('main_user_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
        });

        // مطالب قبلی: ثبت‌کننده‌ی واقعی همان user_id بوده است
        DB::table('posts')->whereNull('main_user_id')->update(['main_user_id' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('main_user_id');
        });
    }
};
