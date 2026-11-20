<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public const COOLDOWN = 120;      // فاصله‌ی بین دو ارسال (ثانیه)
    public const TTL = 300;           // مدت اعتبار کد (ثانیه)
    public const MAX_ATTEMPTS = 5;    // حداکثر تلاش برای وارد کردن کد
    public const MAX_PER_HOUR = 5;    // حداکثر ارسال در ساعت برای هر شماره
    public const MAX_PER_HOUR_IP = 20; // حداکثر ارسال در ساعت برای هر IP

    public function __construct(private AmootService $sms)
    {
    }

    public function send(string $mobile, string $purpose): void
    {
        $cooldownKey = "otp-cooldown:{$purpose}:{$mobile}";
        $mobileKey = "otp-send-mobile:{$mobile}";
        $ipKey = 'otp-send-ip:'.request()->ip();

        if ($left = $this->cooldownLeft($mobile, $purpose)) {
            throw new OtpException("لطفا {$left} ثانیه دیگر برای دریافت دوباره‌ی کد صبر کنید.");
        }

        if (RateLimiter::tooManyAttempts($mobileKey, self::MAX_PER_HOUR)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_PER_HOUR_IP)) {
            throw new OtpException('تعداد درخواست‌های شما زیاد است. بعدا دوباره تلاش کنید.');
        }

        $code = (string) random_int(10000, 99999);

        try {
            $this->sms->sendVerify($mobile, $code);
        } catch (OtpException $e) {
            throw $e;
        }

        Cache::put($this->codeKey($mobile, $purpose), [
            'hash' => $this->hash($code),
            'attempts' => 0,
        ], self::TTL);

        Cache::put($cooldownKey, now()->addSeconds(self::COOLDOWN)->timestamp, self::COOLDOWN);

        RateLimiter::hit($mobileKey, 3600);
        RateLimiter::hit($ipKey, 3600);
    }

    public function cooldownLeft(string $mobile, string $purpose): int
    {
        $until = Cache::get("otp-cooldown:{$purpose}:{$mobile}");

        return $until ? max(0, $until - now()->timestamp) : 0;
    }

    /** در صورت نادرست بودن کد، OtpException پرتاب می‌شود. */
    public function verify(string $mobile, string $purpose, string $code): void
    {
        $key = $this->codeKey($mobile, $purpose);
        $data = Cache::get($key);

        if (! $data) {
            throw new OtpException('کد منقضی شده یا نامعتبر است. کد جدید دریافت کنید.');
        }

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);
            throw new OtpException('تعداد تلاش‌های ناموفق زیاد است. کد جدید دریافت کنید.');
        }

        if (! hash_equals($data['hash'], $this->hash($this->normalizeDigits($code)))) {
            $data['attempts']++;
            Cache::put($key, $data, self::TTL);
            throw new OtpException('کد وارد شده صحیح نیست.');
        }

        Cache::forget($key); // کد یک‌بار مصرف است
    }

    private function codeKey(string $mobile, string $purpose): string
    {
        return "otp-code:{$purpose}:{$mobile}";
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key'));
    }

    private function normalizeDigits(string $value): string
    {
        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/\D+/', '', $value);
    }
}
