<?php

namespace Hamin\FormBuilder\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'form-builder:install {--force : بازنویسی فایل تنظیمات موجود}';

    protected $description = 'نصب فرم‌ساز: کپی تنظیمات و ساخت جدول‌ها';

    public function handle(): int
    {
        $this->components->info('نصب فرم‌ساز');

        $this->callSilently('vendor:publish', [
            '--tag' => 'form-builder-config',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->components->task('فایل config/form-builder.php');

        $this->call('migrate', ['--force' => true]);

        $this->newLine();
        $this->components->bulletList([
            'لایوت پنل و سایت را در config/form-builder.php تنظیم کنید (admin.layout و public.layout).',
            'Gate «'.(config('form-builder.admin.gate') ?? '—').'» را برای مدیران تعریف کنید، یا admin.gate را null بگذارید.',
            'فیلدهای پروفایل (profile_fields) را با ستون‌های جدول کاربران پروژه هماهنگ کنید.',
            'پنل فرم‌ها: /'.trim(config('form-builder.admin.prefix'), '/').'  —  صفحه فرم: /'.trim(config('form-builder.public.prefix'), '/').'/{slug}',
        ]);

        return self::SUCCESS;
    }
}
