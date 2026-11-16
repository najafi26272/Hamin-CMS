<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * تنظیمات سایت (کلید/مقدار). همه مقادیر یک‌جا cache می‌شوند و با هر ذخیره پاک می‌شود.
 *
 *   Setting::get('site_title')        Setting::set(['site_title' => '...'])
 *   Setting::siteTitle()  Setting::logoUrl()  Setting::faviconUrl()  Setting::maintenance()
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'site-settings';

    // مقادیر پیش‌فرض وقتی تنظیمی ذخیره نشده باشد
    public const DEFAULTS = [
        'site_title' => 'مجمع هنری هامین',
        'site_description' => 'مجمع هنری هامین؛ لوح، شنیداری، تماشایی، شعر و رویدادهای هنری',
        'logo' => null,
        'favicon' => null,
        'maintenance_mode' => '0',
        'maintenance_message' => null,
    ];

    // نقش‌هایی که در حالت توسعه می‌توانند وارد شوند و سایت را ببینند
    public const MAINTENANCE_ROLES = ['admin', 'author'];

    // همه تنظیمات ذخیره‌شده: key => value
    public static function values(): array
    {
        // یک بار در هر درخواست (نگهداری در container)
        if (app()->bound(self::CACHE_KEY)) {
            return app(self::CACHE_KEY);
        }

        try {
            $values = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable) {
            // پیش از اجرای migration جدول وجود ندارد
            return [];
        }

        app()->instance(self::CACHE_KEY, $values);

        return $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::values()[$key] ?? null;

        return $value === null || $value === '' ? ($default ?? self::DEFAULTS[$key] ?? null) : $value;
    }

    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => is_bool($value) ? ($value ? '1' : '0') : $value]);
        }

        self::flush();
    }

    public static function flush(): void
    {
        app()->forgetInstance(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY);
    }

    public static function siteTitle(): string
    {
        return (string) self::get('site_title');
    }

    public static function siteDescription(): string
    {
        return (string) self::get('site_description');
    }

    public static function logoUrl(): string
    {
        $logo = self::get('logo');

        return $logo ? Storage::disk('public')->url($logo) : asset('assets/front/images/logo.png');
    }

    public static function faviconUrl(): string
    {
        $favicon = self::get('favicon');

        return $favicon ? Storage::disk('public')->url($favicon) : asset('assets/front/images/icon.png');
    }

    public static function maintenance(): bool
    {
        return self::get('maintenance_mode') === '1';
    }

    public static function maintenanceMessage(): string
    {
        return (string) self::get('maintenance_message', 'سایت در حال توسعه و به‌روزرسانی است. به‌زودی با امکانات تازه برمی‌گردیم.');
    }
}
