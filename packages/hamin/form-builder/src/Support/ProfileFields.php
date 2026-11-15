<?php

namespace Hamin\FormBuilder\Support;

use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;

// فیلدهای پایگاه داده‌ای: خواندن مقدار از پروفایل کاربر بر اساس config('form-builder.profile_fields')
class ProfileFields
{
    public static function all(): array
    {
        return config('form-builder.profile_fields', []);
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function exists(string $key): bool
    {
        return self::get($key) !== null;
    }

    public static function userModel(): string
    {
        return config('form-builder.user_model')
            ?? config('auth.providers.'.config('auth.guards.'.config('auth.defaults.guard').'.provider').'.model')
            ?? \App\Models\User::class;
    }

    // مقدار فیلد پروفایل برای کاربر؛ رشته خالی اگر کاربر یا ستون وجود ندارد
    public static function value(string $key, ?Authenticatable $user): string
    {
        $config = self::get($key);

        if (! $config || ! $user) {
            return '';
        }

        $attributes = (array) ($config['attribute'] ?? $key);
        $parts = [];

        foreach ($attributes as $attribute) {
            $value = data_get($user, $attribute);

            if ($value instanceof DateTimeInterface) {
                $value = self::formatDate($value);
            }

            if ($value !== null && $value !== '') {
                $parts[] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return trim(implode(' ', $parts));
    }

    // تاریخ به شمسی (اگر پکیج jalali نصب باشد)، وگرنه میلادی
    private static function formatDate(DateTimeInterface $date): string
    {
        return function_exists('jdate') ? jdate($date)->format('Y/m/d') : $date->format('Y-m-d');
    }
}
