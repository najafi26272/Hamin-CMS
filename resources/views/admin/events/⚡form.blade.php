<?php

use App\Models\Event;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public ?Event $event = null;

    public string $title = '';
    public string $slug = '';
    public string $description = '';
    public $poster = null;               // پوستر جدید
    public bool $removePoster = false;   // حذف پوستر فعلی با ذخیره
    public ?int $starts_at = null;       // unix timestamp (ثانیه)؛ در تقویم شمسی و ساعت تهران نمایش داده می‌شود
    public ?int $ends_at = null;
    public bool $is_active = true;

    // اگر کاربر slug را دستی تغییر دهد، دیگر از روی عنوان ساخته نمی‌شود
    public bool $slugTouched = false;

    public function mount(Event $event): void
    {
        if (! $event->exists) {
            return;
        }

        $this->event = $event;

        $this->title = $event->title;
        $this->slug = $event->slug;
        $this->description = $event->description ?? '';
        $this->starts_at = $event->starts_at->timestamp;
        $this->ends_at = $event->ends_at->timestamp;
        $this->is_active = $event->is_active;

        // در ویرایش، slug قبلی حفظ می‌شود تا آدرس‌ها تغییر نکنند
        $this->slugTouched = true;
    }

    public function updatedTitle(): void
    {
        if (! $this->slugTouched) {
            $this->slug = Event::uniqueSlug($this->title, $this->event?->id);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    public function generateSlug(): void
    {
        $this->slug = Event::uniqueSlug($this->title, $this->event?->id);
        $this->slugTouched = false;
        $this->resetValidation('slug');
    }

    public function updatedPoster(): void
    {
        $this->validateOnly('poster');
        $this->removePoster = false;
    }

    public function clearPoster(): void
    {
        $this->poster = null;
        $this->removePoster = true;
        $this->resetValidation('poster');
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'max:190', 'regex:/^[\p{L}\p{N}_]+$/u', Rule::unique('events', 'slug')->ignore($this->event?->id)],
            'description' => ['nullable', 'string'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'starts_at' => ['required', 'integer'],
            'ends_at' => ['required', 'integer', 'gte:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'integer' => ':attribute معتبر نیست.',
            'slug.regex' => 'slug فقط می‌تواند شامل حروف، عدد و _ باشد.',
            'starts_at.required' => 'تاریخ شروع را از تقویم انتخاب کنید.',
            'ends_at.required' => 'تاریخ پایان را از تقویم انتخاب کنید.',
            'ends_at.gte' => 'تاریخ پایان نباید قبل از تاریخ شروع باشد.',
            'poster.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'poster.mimes' => 'فرمت پوستر باید jpg، png یا webp باشد.',
            'poster.max' => 'حجم پوستر نباید بیشتر از ۴ مگابایت باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'عنوان',
            'slug' => 'slug',
            'description' => 'توضیحات',
            'poster' => 'پوستر',
            'starts_at' => 'تاریخ شروع',
            'ends_at' => 'تاریخ پایان',
        ];
    }

    public function save()
    {
        // slug دستی هم با همان قوانین استاندارد اصلاح می‌شود
        $this->slug = Event::makeSlug($this->slug);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'title' => trim($this->title),
            'slug' => $this->slug,
            'description' => HtmlSanitizer::clean($this->description) ?: null,
            'starts_at' => Carbon::createFromTimestamp($this->starts_at),
            'ends_at' => Carbon::createFromTimestamp($this->ends_at),
            'is_active' => $this->is_active,
        ];

        $oldPoster = $this->event?->poster;
        $posterChanged = $this->poster || $this->removePoster;

        try {
            if ($posterChanged) {
                $data['poster'] = $this->poster ? $this->poster->store('events', 'public') : null;
            }

            if ($this->event) {
                $this->event->update($data);
                $message = 'رویداد با موفقیت ویرایش شد.';
            } else {
                Event::create($data + ['user_id' => auth()->id()]);
                $message = 'رویداد جدید با موفقیت ثبت شد.';
            }

            // پوستر قبلی فقط بعد از ذخیره موفق پاک می‌شود
            if ($posterChanged && $oldPoster) {
                Storage::disk('public')->delete($oldPoster);
            }
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('events.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $event ? 'ویرایش رویداد: '.\Illuminate\Support\Str::limit($event->title, 50) : 'ثبت رویداد جدید' }}</h5>

        <a href="{{ route('events.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit="save" novalidate>
        <div class="row">
            {{-- ستون اصلی --}}
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-4">اطلاعات رویداد</h6>

                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="type"></i></span>
                                <input type="text" id="title" wire:model.live.debounce.400ms="title"
                                       class="form-control @error('title') is-invalid @enderror" placeholder="عنوان رویداد">
                                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">slug (نامک) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="link"></i></span>
                                <input type="text" id="slug" wire:model.blur="slug" autocomplete="off" dir="auto"
                                       class="form-control @error('slug') is-invalid @enderror" placeholder="به صورت خودکار از عنوان ساخته می‌شود">
                                <button type="button" wire:click="generateSlug" class="btn btn-outline-secondary" title="ساخت از روی عنوان">
                                    <span wire:ignore><i data-feather="refresh-cw"></i></span>
                                </button>
                                @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-text">فاصله‌ها به _ تبدیل می‌شوند و فقط حروف، عدد و _ مجاز است.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="starts_at_picker" class="form-label">تاریخ و ساعت شروع <span class="text-danger">*</span></label>
                                <div class="input-group" wire:ignore>
                                    <span class="input-group-text"><i data-feather="calendar"></i></span>
                                    <input type="text" id="starts_at_picker" class="form-control bg-body" placeholder="انتخاب از تقویم" readonly autocomplete="off">
                                </div>
                                @error('starts_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="ends_at_picker" class="form-label">تاریخ و ساعت پایان <span class="text-danger">*</span></label>
                                <div class="input-group" wire:ignore>
                                    <span class="input-group-text"><i data-feather="calendar"></i></span>
                                    <input type="text" id="ends_at_picker" class="form-control bg-body" placeholder="انتخاب از تقویم" readonly autocomplete="off">
                                </div>
                                @error('ends_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label">توضیحات</label>
                            <div wire:ignore>
                                <div id="description-editor"></div>
                            </div>
                            @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ستون کناری --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">وضعیت</h6>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" wire:model.live="is_active">
                            <label class="form-check-label" for="is_active">{{ $is_active ? 'فعال — در سایت نمایش داده می‌شود' : 'غیرفعال — در سایت نمایش داده نمی‌شود' }}</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-icon-text w-100" wire:loading.attr="disabled" wire:target="save, poster">
                            <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                            <span wire:loading.remove wire:target="save">{{ $event ? 'ذخیره تغییرات' : 'ثبت رویداد' }}</span>
                            <span wire:loading wire:target="save">در حال ذخیره...</span>
                        </button>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">پوستر</h6>
                        @php($preview = $poster && ! $errors->has('poster') ? $poster->temporaryUrl() : ($removePoster ? null : $event?->poster_url))

                        <div class="position-relative">
                            <label for="poster" class="poster-upload mb-0">
                                @if ($preview)
                                    <img src="{{ $preview }}" alt="پوستر">
                                @else
                                    <span class="text-muted text-center small">
                                        <span wire:ignore><i data-feather="image" class="mb-1"></i></span><br>
                                        انتخاب پوستر
                                    </span>
                                @endif

                                <span class="poster-loading" wire:loading.flex wire:target="poster">
                                    <span class="spinner-border text-light" role="status"></span>
                                </span>
                            </label>

                            @if ($preview)
                                <button type="button" class="btn btn-danger btn-sm poster-remove" title="حذف پوستر" wire:click="clearPoster">
                                    <span wire:ignore><i data-feather="x"></i></span>
                                </button>
                            @endif
                        </div>
                        <input type="file" id="poster" wire:model="poster" accept="image/png,image/jpeg,image/webp" class="d-none">

                        <p class="text-muted small mt-2 mb-0">اختیاری — jpg، png، webp تا ۴ مگابایت</p>
                        @error('poster') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@assets
<script src="{{ asset('assets/admin/vendors/ckeditor5/ckeditor5.umd.js') }}"></script>
<script src="{{ asset('assets/admin/vendors/ckeditor5/translations/fa.umd.js') }}"></script>
<style>
    .poster-upload {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        aspect-ratio: 3 / 4;
        border: 2px dashed var(--bs-border-color);
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
    }

    .poster-upload img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .poster-loading {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        align-items: center;
        justify-content: center;
    }

    .poster-remove {
        position: absolute;
        top: 6px;
        left: 6px;
        width: 28px;
        height: 28px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .poster-remove svg {
        width: 16px;
        height: 16px;
    }

    .ck.ck-editor__editable_inline {
        min-height: 250px;
    }
</style>
@endassets

@script
<script>
    // ویرایشگر متن (CKEditor 5 با امکان راست‌چین، وسط‌چین و چپ‌چین)
    createEditor(document.querySelector('#description-editor'), $wire, 'description');

    // تقویم شمسی همراه با ساعت (به سرور unix timestamp ارسال می‌شود)
    const datetimePicker = (selector, property) => {
        const picker = $(selector).persianDatepicker({
            initialValue: false,
            format: 'YYYY/MM/DD  HH:mm',
            autoClose: false,
            timePicker: {
                enabled: true,
                second: {enabled: false},
                meridian: {enabled: false},
            },
            toolbox: {calendarSwitch: {enabled: false}},
            // تقویم بعد از onSelect ساعت را به‌روز می‌کند، برای همین با setTimeout خوانده می‌شود
            onSelect: () => setTimeout(() => {
                const unix = picker.getState().selected.unixDate;
                $wire.$set(property, Math.floor(unix / 60000) * 60, false); // ثانیه‌ها حذف می‌شوند
            }),
        });

        if ($wire.get(property)) {
            picker.setDate($wire.get(property) * 1000);
        }
    };

    datetimePicker('#starts_at_picker', 'starts_at');
    datetimePicker('#ends_at_picker', 'ends_at');
</script>
@endscript
