# فرم‌ساز هامین (`hamin/form-builder`)

پکیج مستقل لاراول برای ساخت فرم در پنل مدیریت، نمایش آن در سایت و دریافت پاسخ‌ها.
همه فایل‌ها داخل همین پوشه است؛ برای استفاده در پروژه دیگر فقط این پوشه کپی می‌شود.

## امکانات

**دو نوع فیلد**

| نوع | توضیح |
|---|---|
| ورودی خام | کاربر مقدار را وارد می‌کند: متن کوتاه، عدد، متن بلند، لیست کشویی (تک یا چندانتخابی)، چک‌باکس (تکی یا گروهی)، گروه رادیویی، آپلود فایل |
| فیلد پایگاه داده | از پروفایل کاربر خوانده و خودکار پر می‌شود: نام، نام خانوادگی، کد ملی، موبایل، ایمیل، تاریخ تولد و ... — هر فیلد **قابل ویرایش** یا **غیرقابل ویرایش** |

- فیلد غیرقابل ویرایش هنگام ثبت هم دوباره از پایگاه داده خوانده می‌شود؛ تغییر آن از سمت مرورگر اثری ندارد.
- قالب‌های آماده متن: ایمیل، موبایل، کد ملی (با رقم کنترل)، کد پستی، فقط رقم، فقط فارسی، فقط انگلیسی، آدرس وب.
- ارقام فارسی و عربی خودکار به انگلیسی تبدیل می‌شوند.
- آپلود فایل: انتخاب پسوندهای مجاز، حداکثر حجم، یک یا چند فایل و حداکثر تعداد. پسوندهای خطرناک (php، svg، html، exe و ...) هرگز پذیرفته نمی‌شوند. فایل‌ها روی دیسک خصوصی ذخیره و فقط از پنل دانلود می‌شوند.
- تنظیمات فرم: فعال/غیرفعال، فقط کاربران واردشده، یک بار یا چند بار ثبت برای هر کاربر، متن دکمه و پیام موفقیت.
- پنل: لیست فرم‌ها، کپی فرم، ویرایشگر فیلدها (افزودن، ترتیب، تنظیمات هر نوع)، پاسخ‌ها با جزئیات، دانلود فایل‌ها، حذف و خروجی CSV برای اکسل.
- API JSON برای پروژه‌هایی که رابط React / Vue / موبایل دارند.

## پیش‌نیاز

- PHP 8.2+ و Laravel 11، 12 یا 13
- Livewire 4 برای پنل و صفحه فرم آماده (برای استفاده فقط از API لازم نیست)
- پنل آماده با کلاس‌های **Bootstrap 5** نوشته شده است. فرم سایت CSS مستقل دارد.
- اختیاری: `morilog/jalali` برای نمایش تاریخ‌ها به شمسی

## نصب در یک پروژه جدید

۱. پوشه `packages/hamin/form-builder` را در همین مسیر پروژه مقصد کپی کنید.

۲. پکیج را نصب کنید:

```bash
composer config repositories.form-builder path packages/hamin/form-builder
composer require hamin/form-builder:@dev
```

۳. تنظیمات و جدول‌ها:

```bash
php artisan form-builder:install
```

این دستور `config/form-builder.php` را می‌سازد و migration را اجرا می‌کند (سه جدول `form_builder_forms`، `form_builder_fields` و `form_builder_submissions`).

۴. فایل `config/form-builder.php` را با پروژه هماهنگ کنید:

- `admin.layout`: لایوت پنل (مثلا `layouts.app` یا `layouts::admin`). صفحه‌ها داخل `{{ $slot }}` آن قرار می‌گیرند.
- `admin.gate`: مجوز لازم برای پنل (پیش‌فرض `manage-forms`). یا Gate هم‌نام را تعریف کنید یا مقدار را `null` بگذارید تا هر کاربر واردشده دسترسی داشته باشد:
  ```php
  Gate::define('manage-forms', fn ($user) => $user->is_admin);
  ```
- `public.layout` و `public.layout_type`: لایوت صفحه فرم در سایت. برای `@extends('layouts.site')` مقدار `extends` و نام section؛ برای `<x-app-layout>` مقدار `component`.
- `profile_fields`: فیلدهای پروفایل را با ستون‌های جدول کاربران پروژه هماهنگ کنید. ستونی که وجود ندارد خالی برمی‌گردد.

۵. لینک پنل را در منوی پروژه بگذارید: `route('form-builder.admin.forms.index')`

## آدرس‌ها

| آدرس | کاربرد |
|---|---|
| `/panel/forms` | لیست فرم‌ها (پیشوند در `admin.prefix`) |
| `/forms/{slug}` | صفحه فرم در سایت (پیشوند در `public.prefix`) |
| `GET /api/forms/{slug}` | ساختار فرم و مقادیر پروفایل |
| `POST /api/forms/{slug}/submit` | ثبت فرم |

فرم را می‌توان در هر صفحه Livewire دیگری هم گذاشت:

```blade
<livewire:form-builder.form slug="contact" />
```

## API

`GET /api/forms/{slug}` خروجی:

```json
{
  "form": { "title": "...", "requires_login": true, "can_submit": true, "missing_profile_fields": [] },
  "fields": [
    { "name": "mobile", "kind": "profile", "type": "tel", "label": "شماره موبایل", "required": true, "readonly": true, "options": [] },
    { "name": "f_ab12cd", "kind": "input", "type": "select", "multiple": true, "options": ["الف", "ب"] }
  ],
  "values": { "mobile": "09121234567" }
}
```

`POST /api/forms/{slug}/submit` با `multipart/form-data`:

```
values[f_ab12cd][]=الف
values[f_text]=سلام
files[f_upload]=@file.pdf        (چند فایل: files[f_upload][]=...)
```

پاسخ موفق `201` و خطاها `422` با کلیدهای `values.{name}` و `files.{name}`.
برای فرم‌هایی که ورود لازم دارند، `api.middleware` باید کاربر را شناسایی کند (`web` برای نشست، یا `auth:sanctum`).

## سفارشی‌سازی ظاهر

رنگ‌های فرم سایت با متغیرهای CSS تغییر می‌کنند:

```css
.fb-form { --fb-accent: #ff6600; --fb-border: #e7e2da; --fb-radius: 12px; }
.fb-page { --fb-page-border: #e7e2da; --fb-page-radius: 20px; }
```

برای تغییر کامل قالب‌ها:

```bash
php artisan vendor:publish --tag=form-builder-views
```

فایل‌ها در `resources/views/vendor/form-builder` کپی می‌شوند و نسخه پروژه جایگزین نسخه پکیج می‌شود.

## ساختار پوشه

```
config/form-builder.php            تنظیمات
database/migrations/               جدول‌ها
routes/admin.php, public.php, api.php
src/FormBuilderServiceProvider.php ثبت روت‌ها، viewها، کامپوننت‌ها و دستور نصب
src/Models/                        Form، FormField، FormSubmission
src/Support/SubmissionService.php  هسته: مقادیر اولیه، اعتبارسنجی و ذخیره (مستقل از رابط کاربری)
src/Support/ProfileFields.php      خواندن فیلدهای پروفایل کاربر
src/Support/FieldTypes.php         انواع فیلد، قالب‌ها و پسوندهای ممنوع
src/Support/NationalCode.php       اعتبارسنجی کد ملی
src/Livewire/                      پنل مدیریت و نمایش فرم
src/Http/Controllers/              صفحه عمومی، API، دانلود فایل و خروجی CSV
resources/views/                   قالب‌ها (پنل با Bootstrap، فرم با CSS مستقل)
```

## حذف

```bash
php artisan migrate:rollback --path=vendor/hamin/form-builder/database/migrations
composer remove hamin/form-builder
```
