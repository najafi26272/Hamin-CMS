<?php

namespace Hamin\FormBuilder\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// اعتبارسنجی کد ملی ایران (۱۰ رقم با رقم کنترل)
class NationalCode implements ValidationRule
{
    public static function isValid(string $code): bool
    {
        if (! preg_match('/^\d{10}$/', $code) || preg_match('/^(\d)\1{9}$/', $code)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $check = (int) $code[9];

        return $remainder < 2 ? $check === $remainder : $check === 11 - $remainder;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid((string) $value)) {
            $fail('کد ملی وارد شده معتبر نیست.');
        }
    }
}
