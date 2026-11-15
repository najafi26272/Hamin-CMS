<?php

use App\Models\SocialLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

// مدیریت شبکه‌های اجتماعی: همه شبکه‌ها در یک فرم ویرایش و با هم ذخیره می‌شوند
new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    /** @var array<int, array{id:int, platform:string, url:string, icon_url:?string, is_active:bool, sort_order:int}> */
    public array $links = [];

    // آیکن‌های جدید انتخاب‌شده، با کلید شناسه شبکه
    public array $icons = [];

    // شبکه‌هایی که آیکن دلخواهشان باید حذف شود (برگشت به آیکن پیش‌فرض)
    public array $removeIcons = [];

    public function mount(): void
    {
        SocialLink::syncPlatforms();
        $this->loadLinks();
    }

    private function loadLinks(): void
    {
        $this->links = SocialLink::whereIn('platform', array_keys(SocialLink::PLATFORMS))
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (SocialLink $link) => [
                'id' => $link->id,
                'platform' => $link->platform,
                'url' => (string) $link->url,
                'icon_url' => $link->icon_url,
                'is_active' => $link->is_active,
                'sort_order' => $link->sort_order,
            ])->all();
    }

    protected function rules(): array
    {
        return [
            'links.*.url' => ['nullable', 'max:500', 'regex:/^https?:\/\/\S+$/i'],
            'links.*.is_active' => ['boolean'],
            'links.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            // SVG مجاز نیست، چون ممکن است کد مخرب داشته باشد
            'icons.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:512'],
        ];
    }

    public function updatedIcons($value, $key): void
    {
        $this->validateOnly('icons.'.$key);
        unset($this->removeIcons[$key]);
    }

    public function clearIcon(int $id): void
    {
        unset($this->icons[$id]);
        $this->removeIcons[$id] = true;
    }

    public function undoClearIcon(int $id): void
    {
        unset($this->removeIcons[$id]);
    }

    protected function messages(): array
    {
        return [
            'links.*.url.regex' => 'آدرس باید کامل و با http:// یا https:// شروع شود.',
            'links.*.url.max' => 'آدرس نباید بیشتر از ۵۰۰ کاراکتر باشد.',
            'links.*.sort_order.required' => 'ترتیب را وارد کنید.',
            'links.*.sort_order.integer' => 'ترتیب باید عدد باشد.',
            'links.*.sort_order.min' => 'ترتیب نمی‌تواند منفی باشد.',
            'links.*.sort_order.max' => 'ترتیب نباید بیشتر از ۹۹۹ باشد.',
            'icons.*.image' => 'فایل آیکن باید تصویر باشد.',
            'icons.*.mimes' => 'فرمت آیکن باید png، jpg یا webp باشد.',
            'icons.*.max' => 'حجم آیکن نباید بیشتر از ۵۱۲ کیلوبایت باشد.',
        ];
    }

    public function save(): void
    {
        if (Gate::denies('manage-social-links')) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی لازم برای این عملیات را ندارید.');
            return;
        }

        foreach ($this->links as $i => $link) {
            $this->links[$i]['url'] = trim($link['url']);
        }

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        // شبکه فعال باید آدرس داشته باشد
        $errors = [];
        foreach ($this->links as $i => $link) {
            if ($link['is_active'] && $link['url'] === '') {
                $errors["links.$i.url"] = 'برای نمایش در سایت، آدرس را وارد کنید.';
            }
        }
        if ($errors) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw ValidationException::withMessages($errors);
        }

        $stored = [];   // فایل‌های جدید (برای پاک کردن در صورت خطا)
        $obsolete = []; // فایل‌های قدیمی که پس از ذخیره موفق پاک می‌شوند

        try {
            DB::transaction(function () use (&$stored, &$obsolete) {
                foreach ($this->links as $link) {
                    $model = SocialLink::find($link['id']);
                    if (! $model) {
                        continue;
                    }

                    $data = [
                        'url' => $link['url'] ?: null,
                        'is_active' => (bool) $link['is_active'],
                        'sort_order' => (int) $link['sort_order'],
                    ];

                    $upload = $this->icons[$link['id']] ?? null;

                    if ($upload) {
                        $data['icon'] = $stored[] = $upload->store('social-icons', 'public');
                    } elseif (! empty($this->removeIcons[$link['id']])) {
                        $data['icon'] = null;
                    }

                    if (array_key_exists('icon', $data) && $model->icon) {
                        $obsolete[] = $model->icon;
                    }

                    $model->update($data);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            Storage::disk('public')->delete($stored);
            $this->dispatch('notify', type: 'error', message: 'ذخیره با خطا مواجه شد. دوباره تلاش کنید.');
            return;
        }

        Storage::disk('public')->delete($obsolete);

        $this->reset('icons', 'removeIcons');
        $this->loadLinks();
        $this->dispatch('notify', type: 'success', message: 'شبکه‌های اجتماعی با موفقیت ذخیره شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">شبکه‌های اجتماعی</h5>
            <p class="text-muted small mb-0">شبکه‌هایی که فعال باشند و آدرس داشته باشند، در پایین همه صفحه‌های سایت نمایش داده می‌شوند.</p>
        </div>
    </div>

    <form wire:submit="save" novalidate>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th style="width: 160px">شبکه</th>
                            <th style="width: 210px">آیکن</th>
                            <th>آدرس صفحه یا کانال</th>
                            <th style="width: 110px">ترتیب</th>
                            <th style="width: 110px">نمایش در سایت</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($links as $i => $link)
                            @php
                                $cfg = \App\Models\SocialLink::PLATFORMS[$link['platform']];
                            @endphp
                            <tr wire:key="social-{{ $link['id'] }}">
                                <td>
                                    <span class="social-dot" style="background: {{ $cfg['color'] }}"></span>
                                    <span class="fw-bold">{{ $cfg['label'] }}</span>
                                </td>
                                <td>
                                    @php
                                        $id = $link['id'];
                                        $upload = $icons[$id] ?? null;
                                        $removed = ! empty($removeIcons[$id]);
                                        $preview = $upload && ! $errors->has("icons.$id") ? $upload->temporaryUrl() : ($removed ? null : $link['icon_url']);
                                    @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="social-icon-preview" style="--brand: {{ $cfg['color'] }}">
                                            @if ($preview)
                                                <img src="{{ $preview }}" alt="">
                                            @else
                                                <x-site.icon :name="'social-'.$link['platform']" />
                                            @endif
                                            <span class="social-icon-loading" wire:loading.flex wire:target="icons.{{ $id }}">
                                                <span class="spinner-border spinner-border-sm text-light"></span>
                                            </span>
                                        </span>

                                        <label for="social-icon-{{ $id }}" class="btn btn-sm btn-outline-primary mb-0">انتخاب</label>
                                        <input type="file" id="social-icon-{{ $id }}" wire:model="icons.{{ $id }}" accept="image/png,image/jpeg,image/webp" class="d-none">

                                        @if ($upload || ($link['icon_url'] && ! $removed))
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="clearIcon({{ $id }})" title="برگشت به آیکن پیش‌فرض">حذف</button>
                                        @elseif ($removed)
                                            <button type="button" class="btn btn-sm btn-link px-1" wire:click="undoClearIcon({{ $id }})">انصراف</button>
                                        @endif
                                    </div>
                                    @if ($removed && ! $upload)
                                        <small class="text-warning d-block mt-1">پس از ذخیره، آیکن پیش‌فرض نمایش داده می‌شود.</small>
                                    @endif
                                    @error("icons.$id") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </td>
                                <td>
                                    <input type="url" wire:model="links.{{ $i }}.url" dir="ltr"
                                           class="form-control text-start @error("links.$i.url") is-invalid @enderror"
                                           placeholder="{{ $cfg['placeholder'] }}">
                                    @error("links.$i.url") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </td>
                                <td>
                                    <input type="number" min="0" max="999" wire:model="links.{{ $i }}.sort_order"
                                           class="form-control @error("links.$i.sort_order") is-invalid @enderror">
                                    @error("links.$i.sort_order") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </td>
                                <td>
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                               id="social-active-{{ $link['id'] }}" wire:model="links.{{ $i }}.is_active">
                                        <label class="form-check-label" for="social-active-{{ $link['id'] }}"></label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <small class="text-muted d-block mt-3">
                    واتساپ: آدرس به شکل <span dir="ltr">https://wa.me/989121234567</span> (شماره با کد کشور و بدون صفر و +).
                    عدد کوچک‌تر در ترتیب، جلوتر نمایش داده می‌شود.
                    <br>آیکن دلخواه: png، jpg یا webp مربعی (پیشنهاد ۹۶×۹۶ پیکسل با زمینه شفاف) تا ۵۱۲ کیلوبایت. اگر آیکنی آپلود نشود، آیکن پیش‌فرض سایت نمایش داده می‌شود.
                </small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
            <span wire:loading wire:target="save">در حال ذخیره...</span>
        </button>
    </form>
</div>

@assets
<style>
    .social-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-inline-end: 8px;
        vertical-align: middle;
    }

    .social-icon-preview {
        position: relative;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: 50%;
        border: 1px solid var(--bs-border-color);
        display: grid;
        place-items: center;
        color: var(--brand);
        overflow: hidden;
        background: var(--bs-body-bg);
    }

    .social-icon-preview img {
        width: 26px;
        height: 26px;
        object-fit: contain;
    }

    .social-icon-preview .icon {
        width: 20px;
        height: 20px;
    }

    .social-icon-loading {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        align-items: center;
        justify-content: center;
    }
</style>
@endassets
