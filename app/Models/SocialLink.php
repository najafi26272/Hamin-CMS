<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    /**
     * شبکه‌های اجتماعی پشتیبانی‌شده
     * color: رنگ برند برای حالت hover در سایت
     * placeholder: نمونه آدرس در فرم پنل
     */
    public const PLATFORMS = [
        'eitaa' => ['label' => 'ایتا', 'color' => '#f7931e', 'placeholder' => 'https://eitaa.com/username'],
        'telegram' => ['label' => 'تلگرام', 'color' => '#229ed9', 'placeholder' => 'https://t.me/username'],
        'instagram' => ['label' => 'اینستاگرام', 'color' => '#e1306c', 'placeholder' => 'https://instagram.com/username'],
        'whatsapp' => ['label' => 'واتساپ', 'color' => '#25d366', 'placeholder' => 'https://wa.me/989121234567'],
        'aparat' => ['label' => 'آپارات', 'color' => '#ed145b', 'placeholder' => 'https://www.aparat.com/username'],
        'youtube' => ['label' => 'یوتیوب', 'color' => '#ff0000', 'placeholder' => 'https://www.youtube.com/@username'],
    ];

    protected $fillable = ['platform', 'url', 'icon', 'is_active', 'sort_order'];

    // آدرس آیکن آپلودشده (null یعنی آیکن پیش‌فرض)
    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->icon) : null;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // لینک‌های قابل نمایش در سایت: فعال، دارای آدرس و شبکه شناخته‌شده
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)->whereNotNull('url')
            ->whereIn('platform', array_keys(self::PLATFORMS))
            ->orderBy('sort_order')->orderBy('id');
    }

    // اطمینان از وجود یک ردیف برای هر شبکه (اگر شبکه جدیدی به PLATFORMS اضافه شود)
    public static function syncPlatforms(): void
    {
        $existing = static::pluck('platform')->all();
        $order = (int) static::max('sort_order');

        foreach (array_keys(self::PLATFORMS) as $platform) {
            if (! in_array($platform, $existing, true)) {
                static::create(['platform' => $platform, 'sort_order' => ++$order]);
            }
        }
    }

    public function getLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform]['label'] ?? $this->platform;
    }

    public function getColorAttribute(): string
    {
        return self::PLATFORMS[$this->platform]['color'] ?? '#19786f';
    }
}
