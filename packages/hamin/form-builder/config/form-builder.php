<?php

/*
|--------------------------------------------------------------------------
| تنظیمات فرم‌ساز
|--------------------------------------------------------------------------
| این فایل با `php artisan form-builder:install` در config/form-builder.php
| پروژه کپی می‌شود. برای هر پروژه فقط همین فایل تغییر می‌کند.
*/

return [

    // مدل کاربر (برای ثبت‌کننده فرم و خواندن فیلدهای پروفایل). null = مدل پیش‌فرض auth
    'user_model' => null,

    /*
    | پنل مدیریت (Livewire + Bootstrap 5)
    | layout: لایوت پنل پروژه. صفحه‌ها داخل {{ $slot }} آن نمایش داده می‌شوند.
    | gate: نام Gate یا مجوز لازم. null یعنی هر کاربر واردشده.
    */
    'admin' => [
        'enabled' => true,
        'prefix' => 'panel/forms',
        'middleware' => ['web', 'auth'],
        'gate' => 'manage-forms',
        'layout' => 'layouts::admin',
    ],

    /*
    | صفحه عمومی فرم: /forms/{slug}
    | layout: لایوتی که صفحه با @extends از آن ارث می‌برد، و section محتوای آن.
    | اگر لایوت پروژه component است (با {{ $slot }})، مقدار layout_type را 'component' بگذارید.
    */
    'public' => [
        'enabled' => true,
        'prefix' => 'forms',
        'middleware' => ['web'],
        'layout' => 'layouts.site',
        'layout_type' => 'extends', // extends | component
        'section' => 'content',
        'login_route' => 'login',    // نام روت ورود برای فرم‌هایی که ورود لازم دارند
        'profile_url' => null,       // آدرس صفحه تکمیل پروفایل (اختیاری)
    ],

    /*
    | API JSON (برای پروژه‌هایی با رابط React / Vue / موبایل)
    | GET  {prefix}/{slug}         ساختار فرم و مقادیر پروفایل
    | POST {prefix}/{slug}/submit  ثبت فرم (multipart/form-data)
    | برای فرم‌هایی که ورود لازم دارند، middleware باید کاربر را شناسایی کند (مثلا web یا auth:sanctum).
    */
    'api' => [
        'enabled' => true,
        'prefix' => 'api/forms',
        'middleware' => ['web'],
    ],

    // محل ذخیره فایل‌های آپلودی. دیسک خصوصی (local) پیشنهاد می‌شود؛ دانلود فقط از پنل مدیریت.
    'storage' => [
        'disk' => 'local',
        'path' => 'form-builder',
    ],

    // محدودیت‌های کلی آپلود (تنظیمات هر فیلد نمی‌تواند از این‌ها بیشتر شود)
    'uploads' => [
        'max_size_kb' => 20480,
        'max_files' => 10,
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'webp', 'gif',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
            'zip', 'rar', 'mp3', 'wav', 'mp4', 'mov',
        ],
    ],

    /*
    | فیلدهای پایگاه داده‌ای (پروفایل کاربر)
    | کلید => [label, attribute, input, format]
    | attribute: نام ستون مدل کاربر؛ آرایه یعنی چند ستون با فاصله کنار هم (مثل نام کامل)
    | input: text | tel | email | date | number
    | format: اعتبارسنجی وقتی کاربر اجازه ویرایش دارد (mobile, email, national_code, ...)
    | ستونی که در جدول کاربران نباشد، خالی برگردانده می‌شود.
    */
    'profile_fields' => [
        'first_name' => ['label' => 'نام', 'attribute' => 'first_name', 'input' => 'text'],
        'last_name' => ['label' => 'نام خانوادگی', 'attribute' => 'last_name', 'input' => 'text'],
        'full_name' => ['label' => 'نام و نام خانوادگی', 'attribute' => ['first_name', 'last_name'], 'input' => 'text'],
        'national_code' => ['label' => 'کد ملی', 'attribute' => 'national_code', 'input' => 'text', 'format' => 'national_code'],
        'mobile' => ['label' => 'شماره موبایل', 'attribute' => 'mobile', 'input' => 'tel', 'format' => 'mobile'],
        'phone' => ['label' => 'تلفن ثابت', 'attribute' => 'phone', 'input' => 'tel', 'format' => 'digits'],
        'email' => ['label' => 'ایمیل', 'attribute' => 'email', 'input' => 'email', 'format' => 'email'],
        'birth_date' => ['label' => 'تاریخ تولد', 'attribute' => 'birth_date', 'input' => 'date'],
        'city' => ['label' => 'شهر', 'attribute' => 'city', 'input' => 'text'],
        'education_degree' => ['label' => 'مدرک تحصیلی', 'attribute' => 'education_degree', 'input' => 'text'],
        'username' => ['label' => 'نام کاربری', 'attribute' => 'username', 'input' => 'text'],
    ],
];
