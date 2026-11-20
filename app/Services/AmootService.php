<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ارسال پیامک با وب‌سرویس REST سامانه‌ی پیامک آموت (portal.amootsms.com)
 */
class AmootService
{
    private const BASE = 'https://portal.amootsms.com/rest';

    /**
     * ارسال کد تأیید.
     * - اگر AMOOT_PATTERN_ID تنظیم شده باشد: متد SendWithPattern (الگوی خودتان، مقدار اول = کد)
     * - در غیر این صورت: متد SendQuickOTP (الگوی OTP با متغیر code) و کد ما به‌عنوان OptionalCode ارسال می‌شود
     */
    public function sendVerify(string $mobile, string $code): void
    {
        if (config('services.amoot.driver') === 'log') {
            Log::info("[Amoot:log] verify to {$mobile}: {$code}");

            return;
        }

        // طبق مستندات آموت شماره بدون صفر ابتدایی ارسال می‌شود: 9123456789
        $receptor = ltrim($mobile, '0');

        if ($patternId = config('services.amoot.pattern_id')) {
            $this->call('SendWithPattern', [
                'Mobile' => $receptor,
                'PatternCodeID' => $patternId,
                'PatternValues' => $code,
            ]);

            return;
        }

        $this->call('SendQuickOTP', [
            'Mobile' => $receptor,
            'CodeLength' => strlen($code),
            'OptionalCode' => $code,
        ]);
    }

    /** ارسال پیامک ساده (اختیاری؛ برای اعلان‌ها) */
    public function send(string $mobile, string $message): void
    {
        if (config('services.amoot.driver') === 'log') {
            Log::info("[Amoot:log] sms to {$mobile}: {$message}");

            return;
        }

        $this->call('SendSimple', [
            'SendDateTime' => now('Asia/Tehran')->format('c'),
            'SMSMessageText' => $message,
            'LineNumber' => config('services.amoot.line', 'public'),
            'Mobiles' => ltrim($mobile, '0'),
        ]);
    }

    private function call(string $method, array $params): array
    {
        $token = config('services.amoot.token');

        if (! $token) {
            throw new OtpException('سامانه پیامک تنظیم نشده است.');
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => $token])
                ->asForm()
                ->post(self::BASE.'/'.$method, ['Token' => $token] + $params);
        } catch (Throwable $e) {
            Log::error('Amoot connection error: '.$e->getMessage());
            throw new OtpException('ارتباط با سامانه پیامک برقرار نشد. کمی بعد دوباره تلاش کنید.');
        }

        $data = $response->json() ?? [];
        $status = strtolower((string) ($data['Status'] ?? ''));

        if (! $response->successful() || $status !== 'success') {
            // جزئیات خطا فقط در لاگ ذخیره می‌شود، نه برای کاربر
            Log::error('Amoot error', ['method' => $method, 'http' => $response->status(), 'body' => $response->body()]);
            throw new OtpException('ارسال پیامک ناموفق بود. کمی بعد دوباره تلاش کنید.');
        }

        return $data;
    }
}
