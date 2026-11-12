<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'viewable_type', 'viewable_id', 'url', 'path', 'referer', 'ip_address', 'user_agent', 'user_id', 'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function viewable()
    {
        return $this->morphTo();
    }

    /**
     * شروع بازه‌های آمار بر اساس تقویم شمسی و ساعت تهران
     * (امروز، این هفته از شنبه، این ماه، امسال از ۱ فروردین)
     */
    public static function periodStarts(): array
    {
        $now = Jalalian::now(new \DateTimeZone('Asia/Tehran'));

        $start = fn (Jalalian $date) => Carbon::instance($date->toCarbon())->startOfDay()->utc();

        return [
            'today' => $start($now),
            'week' => $start($now->getFirstDayOfWeek()),
            'month' => $start($now->getFirstDayOfMonth()),
            'year' => $start($now->getFirstDayOfYear()),
        ];
    }

    // تعداد بازدید و بازدیدکننده یکتا (بر اساس IP) از یک زمان تا الان
    public static function statsSince(Carbon $from): array
    {
        $query = static::where('viewed_at', '>=', $from);

        return [
            'views' => (clone $query)->count(),
            'visitors' => (clone $query)->distinct()->count('ip_address'),
        ];
    }
}
