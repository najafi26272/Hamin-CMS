<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

// تنظیمات عمومی سایت: عنوان، توضیحات، لوگو، فاوآیکن و حالت توسعه
new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public string $site_title = '';
    public string $site_description = '';
    public bool $maintenance_mode = false;
    public string $maintenance_message = '';

    // فایل‌های جدید
    public $logo = null;
    public $favicon = null;

    // برگشت به تصویر پیش‌فرض
    public bool $removeLogo = false;
    public bool $removeFavicon = false;

    public function mount(): void
    {
        $values = Setting::values();

        $this->site_title = Setting::siteTitle();
        $this->site_description = Setting::siteDescription();
        $this->maintenance_mode = Setting::maintenance();
        $this->maintenance_message = (string) ($values['maintenance_message'] ?? '');
    }

    protected function rules(): array
    {
        return [
            'site_title' => ['required', 'string', 'max:100'],
            'site_description' => ['nullable', 'string', 'max:300'],
            'maintenance_mode' => ['boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            // SVG مجاز نیست، چون ممکن است کد مخرب داشته باشد
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'favicon' => ['nullable', 'file', 'extensions:png,ico', 'mimes:png,ico', 'max:256'],
        ];
    }

    protected function messages(): array
    {
        return [
            'site_title.required' => 'عنوان سایت را وارد کنید.',
            'site_title.max' => 'عنوان سایت نباید بیشتر از ۱۰۰ کاراکتر باشد.',
            'site_description.max' => 'توضیحات نباید بیشتر از ۳۰۰ کاراکتر باشد.',
            'maintenance_message.max' => 'متن صفحه در حال توسعه نباید بیشتر از ۵۰۰ کاراکتر باشد.',
            'logo.image' => 'لوگو باید تصویر باشد.',
            'logo.mimes' => 'فرمت لوگو باید png، jpg یا webp باشد.',
            'logo.max' => 'حجم لوگو نباید بیشتر از ۱ مگابایت باشد.',
            'favicon.extensions' => 'فرمت فاوآیکن باید png یا ico باشد.',
            'favicon.mimes' => 'فرمت فاوآیکن باید png یا ico باشد.',
            'favicon.max' => 'حجم فاوآیکن نباید بیشتر از ۲۵۶ کیلوبایت باشد.',
        ];
    }

    public function updatedLogo(): void
    {
        $this->validateOnly('logo');
        $this->removeLogo = false;
    }

    public function updatedFavicon(): void
    {
        $this->validateOnly('favicon');
        $this->removeFavicon = false;
    }

    public function clearImage(string $field): void
    {
        if ($field === 'logo') {
            $this->logo = null;
            $this->removeLogo = true;
        } elseif ($field === 'favicon') {
            $this->favicon = null;
            $this->removeFavicon = true;
        }
    }

    public function save(): void
    {
        if (Gate::denies('manage-settings')) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی لازم برای این عملیات را ندارید.');
            return;
        }

        $this->site_title = trim($this->site_title);
        $this->site_description = trim($this->site_description);
        $this->maintenance_message = trim($this->maintenance_message);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $disk = Storage::disk('public');
        $values = [
            'site_title' => $this->site_title,
            'site_description' => $this->site_description ?: null,
            'maintenance_mode' => $this->maintenance_mode,
            'maintenance_message' => $this->maintenance_message ?: null,
        ];

        $stored = [];
        $obsolete = [];

        foreach (['logo' => $this->removeLogo, 'favicon' => $this->removeFavicon] as $field => $remove) {
            $old = Setting::get($field);

            if ($this->{$field}) {
                $values[$field] = $stored[] = $this->{$field}->store('settings', 'public');
            } elseif ($remove) {
                $values[$field] = null;
            } else {
                continue;
            }

            if ($old) {
                $obsolete[] = $old;
            }
        }

        try {
            Setting::set($values);
        } catch (\Throwable $e) {
            report($e);
            $disk->delete($stored);
            $this->dispatch('notify', type: 'error', message: 'ذخیره با خطا مواجه شد. دوباره تلاش کنید.');
            return;
        }

        $disk->delete($obsolete);

        $this->reset('logo', 'favicon', 'removeLogo', 'removeFavicon');
        $this->dispatch('notify', type: 'success', message: 'تنظیمات سایت ذخیره شد.');
    }
};
?>

@php
    $logoUrl = \App\Models\Setting::logoUrl();
    $faviconUrl = \App\Models\Setting::faviconUrl();
    $hasCustomLogo = (bool) \App\Models\Setting::get('logo');
    $hasCustomFavicon = (bool) \App\Models\Setting::get('favicon');
    $maintenanceActive = \App\Models\Setting::maintenance();
@endphp
<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">تنظیمات سایت</h5>
            <p class="text-muted small mb-0">عنوان، توضیحات، لوگو و فاوآیکن سایت و حالت توسعه</p>
        </div>
        <a href="{{ route('site.home') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i data-feather="external-link" class="icon-sm me-1"></i> مشاهده سایت
        </a>
    </div>

    @if ($maintenanceActive)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i data-feather="alert-triangle" class="icon-md flex-shrink-0"></i>
            <span>حالت توسعه فعال است. بازدیدکنندگان صفحه «در حال توسعه» را می‌بینند و فقط مدیر اصلی و نویسندگان می‌توانند وارد شوند.</span>
        </div>
    @endif

    <form wire:submit="save" novalidate>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3">اطلاعات اصلی</h6>

                        <div class="mb-3">
                            <label for="site_title" class="form-label">عنوان اصلی سایت <span class="text-danger">*</span></label>
                            <input type="text" id="site_title" wire:model="site_title" maxlength="100"
                                   class="form-control @error('site_title') is-invalid @enderror">
                            @error('site_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">در عنوان تب مرورگر، پایین صفحه‌ها و نتایج جستجو نمایش داده می‌شود.</small>
                        </div>

                        <div class="mb-0">
                            <label for="site_description" class="form-label">توضیحات سایت</label>
                            <textarea id="site_description" wire:model="site_description" rows="3" maxlength="300"
                                      class="form-control @error('site_description') is-invalid @enderror"></textarea>
                            @error('site_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">توضیح کوتاهی که موتورهای جستجو زیر عنوان سایت نشان می‌دهند (حداکثر ۳۰۰ کاراکتر).</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div @class(['card h-100', 'border-warning' => $maintenance_mode])>
                    <div class="card-body">
                        <h6 class="card-title mb-3">حالت توسعه</h6>

                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" role="switch" id="maintenance_mode" wire:model.live="maintenance_mode">
                            <label class="form-check-label fw-bold" for="maintenance_mode">نمایش صفحه «در حال توسعه هستیم»</label>
                        </div>
                        <p class="text-muted small">
                            وقتی فعال باشد، به جای سایت صفحه «در حال توسعه» نمایش داده می‌شود و از فرم ورود فقط
                            <strong>مدیر اصلی</strong> و <strong>نویسندگان</strong> می‌توانند وارد شوند. این افراد سایت را عادی می‌بینند.
                            کاربران دیگری که وارد شده‌اند از حساب خارج می‌شوند.
                        </p>

                        <div x-show="$wire.maintenance_mode" x-cloak>
                            <label for="maintenance_message" class="form-label">متن صفحه در حال توسعه</label>
                            <textarea id="maintenance_message" wire:model="maintenance_message" rows="3" maxlength="500"
                                      class="form-control @error('maintenance_message') is-invalid @enderror"
                                      placeholder="{{ \App\Models\Setting::maintenanceMessage() }}"></textarea>
                            @error('maintenance_message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">خالی بماند، متن پیش‌فرض نمایش داده می‌شود.</small>
                        </div>
                    </div>
                </div>
            </div>

            @foreach ([
                'logo' => ['title' => 'لوگو', 'url' => $logoUrl, 'custom' => $hasCustomLogo, 'remove' => $removeLogo, 'accept' => 'image/png,image/jpeg,image/webp',
                           'hint' => 'png، jpg یا webp تا ۱ مگابایت. پیشنهاد: افقی با زمینه شفاف و ارتفاع حداقل ۱۰۰ پیکسل.'],
                'favicon' => ['title' => 'فاوآیکن', 'url' => $faviconUrl, 'custom' => $hasCustomFavicon, 'remove' => $removeFavicon, 'accept' => '.png,.ico,image/png,image/x-icon',
                              'hint' => 'آیکن تب مرورگر. png یا ico مربعی تا ۲۵۶ کیلوبایت (پیشنهاد ۱۹۲×۱۹۲ پیکسل).'],
            ] as $field => $cfg)
                @php
                    $upload = $this->{$field};
                    $preview = $cfg['url'];
                    if ($upload && ! $errors->has($field)) {
                        $preview = $upload->isPreviewable() ? $upload->temporaryUrl() : null;
                    } elseif ($cfg['remove']) {
                        $preview = $field === 'logo' ? asset('assets/front/images/logo.png') : asset('assets/front/images/icon.png');
                    }
                @endphp
                <div class="col-md-6" wire:key="setting-image-{{ $field }}">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-3">{{ $cfg['title'] }}</h6>

                            <div @class(['settings-preview', 'settings-preview-square' => $field === 'favicon'])>
                                @if ($preview)
                                    <img src="{{ $preview }}" alt="">
                                @else
                                    <span class="text-muted small">{{ $upload?->getClientOriginalName() }}</span>
                                @endif
                                <span class="settings-preview-loading" wire:loading.flex wire:target="{{ $field }}">
                                    <span class="spinner-border spinner-border-sm text-light"></span>
                                </span>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                <label for="setting-{{ $field }}" class="btn btn-sm btn-outline-primary mb-0">انتخاب تصویر</label>
                                <input type="file" id="setting-{{ $field }}" wire:model="{{ $field }}" accept="{{ $cfg['accept'] }}" class="d-none">

                                @if ($upload || ($cfg['custom'] && ! $cfg['remove']))
                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="clearImage('{{ $field }}')">برگشت به پیش‌فرض</button>
                                @endif

                                @if ($upload)
                                    <span class="badge bg-info">پس از ذخیره اعمال می‌شود</span>
                                @elseif ($cfg['remove'])
                                    <span class="badge bg-warning text-dark">پس از ذخیره، تصویر پیش‌فرض نمایش داده می‌شود</span>
                                @endif
                            </div>
                            @error($field) <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                            <small class="text-muted d-block mt-2">{{ $cfg['hint'] }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled" wire:target="save,logo,favicon">
            <span wire:loading.remove wire:target="save">ذخیره تنظیمات</span>
            <span wire:loading wire:target="save">در حال ذخیره...</span>
        </button>
    </form>
</div>

