<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasSlug, SoftDeletes;

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'poster', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // رویدادهای فعال که هنوز تمام نشده‌اند، به ترتیب زمان شروع (برای سایت)
    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)->where('ends_at', '>=', now())->orderBy('starts_at');
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster ? Storage::disk('public')->url($this->poster) : null;
    }

    // زمان‌بندی رویداد: upcoming (پیش رو)، ongoing (در حال برگزاری)، finished (پایان یافته)
    public function getTimingAttribute(): string
    {
        return match (true) {
            $this->starts_at->isFuture() => 'upcoming',
            $this->ends_at->isPast() => 'finished',
            default => 'ongoing',
        };
    }

    // تاریخ به شمسی و ساعت تهران، مثل: 1405/08/12 18:00
    public function jalali(string $field, string $format = 'Y/m/d H:i'): ?string
    {
        return $this->$field ? jdate($this->$field->copy()->setTimezone('Asia/Tehran'))->format($format) : null;
    }
}
