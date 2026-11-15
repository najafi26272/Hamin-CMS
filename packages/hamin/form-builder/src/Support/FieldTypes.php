<?php

namespace Hamin\FormBuilder\Support;

// انواع ورودی خام و تنظیمات پیش‌فرض هر کدام
class FieldTypes
{
    public const TYPES = [
        'text' => 'متن کوتاه',
        'number' => 'عدد',
        'textarea' => 'متن بلند',
        'select' => 'لیست کشویی',
        'checkbox' => 'چک‌باکس',
        'radio' => 'گروه رادیویی',
        'file' => 'آپلود فایل',
    ];

    // قالب‌های اعتبارسنجی متن کوتاه
    public const TEXT_FORMATS = [
        '' => 'آزاد',
        'email' => 'ایمیل',
        'mobile' => 'شماره موبایل',
        'national_code' => 'کد ملی',
        'postal_code' => 'کد پستی',
        'digits' => 'فقط رقم',
        'persian' => 'فقط حروف فارسی',
        'latin' => 'فقط حروف انگلیسی',
        'url' => 'آدرس وب',
    ];

    // پسوندهایی که هرگز پذیرفته نمی‌شوند، حتی اگر در تنظیمات آمده باشند
    public const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'pht', 'phps', 'cgi', 'pl', 'py', 'sh', 'bat', 'cmd',
        'exe', 'msi', 'com', 'js', 'mjs', 'html', 'htm', 'xhtml', 'svg', 'svgz', 'xml', 'htaccess', 'asp', 'aspx', 'jsp',
    ];

    public static function defaults(string $type): array
    {
        return match ($type) {
            'text' => ['format' => '', 'min_length' => null, 'max_length' => 255],
            'number' => ['min' => null, 'max' => null, 'integer' => false],
            'textarea' => ['min_length' => null, 'max_length' => 5000, 'rows' => 4],
            'select' => ['multiple' => false],
            'checkbox' => ['inline' => false],
            'radio' => ['inline' => false],
            'file' => [
                'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size_kb' => 2048,
                'multiple' => false,
                'max_files' => 3,
            ],
            default => [],
        };
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type] ?? $type;
    }

    // پسوندهای مجاز نهایی یک فیلد فایل: اشتراک تنظیمات فیلد و config، بدون پسوندهای خطرناک
    public static function allowedExtensions(array $fieldExtensions): array
    {
        $global = array_map('strtolower', config('form-builder.uploads.allowed_extensions', []));
        $list = array_intersect(array_map('strtolower', $fieldExtensions), $global);

        return array_values(array_diff($list, self::BLOCKED_EXTENSIONS));
    }

    // تبدیل ارقام فارسی و عربی به انگلیسی
    public static function toEnglishDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
