<?php

use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public ?Slider $slider = null;

    public $image = null;          // فایل بنر جدید
    public string $title = '';
    public string $link = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function mount(Slider $slider): void
    {
        if (! $slider->exists) {
            // اسلایدر جدید در انتهای ترتیب قرار می‌گیرد
            $this->sort_order = (int) Slider::max('sort_order') + 1;
            return;
        }

        $this->slider = $slider;

        $this->title = $slider->title;
        $this->link = $slider->link ?? '';
        $this->is_active = $slider->is_active;
        $this->sort_order = $slider->sort_order;
    }

    public function updatedImage(): void
    {
        $this->validateOnly('image');
    }

    protected function rules(): array
    {
        return [
            'image' => [$this->slider ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'title' => ['required', 'string', 'max:190'],
            // آدرس کامل (https://...) یا مسیر داخلی سایت (/posts/...)
            'link' => ['nullable', 'max:500', 'regex:/^(https?:\/\/\S+|\/\S*)$/i'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'image.required' => 'فایل بنر را انتخاب کنید.',
            'image.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'image.mimes' => 'فرمت بنر باید jpg، png یا webp باشد.',
            'image.max' => 'حجم بنر نباید بیشتر از ۴ مگابایت باشد.',
            'link.regex' => 'لینک باید با http:// یا https:// شروع شود، یا یک مسیر داخلی مثل /posts باشد.',
            'sort_order.integer' => 'ترتیب نمایش باید عدد باشد.',
            'sort_order.min' => 'ترتیب نمایش نمی‌تواند منفی باشد.',
            'sort_order.max' => 'ترتیب نمایش نباید بیشتر از ۹۹۹۹ باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'image' => 'فایل بنر',
            'title' => 'عنوان',
            'link' => 'لینک',
            'sort_order' => 'ترتیب نمایش',
        ];
    }

    public function save()
    {
        $this->title = trim($this->title);
        $this->link = trim($this->link);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'title' => $this->title,
            'link' => $this->link ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        $oldImage = $this->slider?->image;

        try {
            if ($this->image) {
                $data['image'] = $this->image->store('sliders', 'public');
            }

            if ($this->slider) {
                $this->slider->update($data);
                $message = 'اسلایدر با موفقیت ویرایش شد.';
            } else {
                Slider::create($data);
                $message = 'اسلایدر جدید با موفقیت ثبت شد.';
            }

            // بنر قبلی فقط بعد از ذخیره موفق پاک می‌شود
            if ($this->image && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('sliders.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $slider ? 'ویرایش اسلایدر: '.\Illuminate\Support\Str::limit($slider->title, 50) : 'ثبت اسلایدر جدید' }}</h5>

        <a href="{{ route('sliders.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit="save" novalidate>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-3">فایل بنر @unless ($slider)<span class="text-danger">*</span>@endunless</h6>

                @php($preview = $image && ! $errors->has('image') ? $image->temporaryUrl() : $slider?->image_url)
                <label for="image" class="banner-upload mb-2">
                    @if ($preview)
                        <img src="{{ $preview }}" alt="بنر">
                    @else
                        <span class="text-muted text-center">
                            <span wire:ignore><i data-feather="image" class="mb-2"></i></span><br>
                            برای انتخاب فایل بنر کلیک کنید
                        </span>
                    @endif

                    <span class="banner-loading" wire:loading.flex wire:target="image">
                        <span class="spinner-border text-light" role="status"></span>
                    </span>
                </label>
                <input type="file" id="image" wire:model="image" accept="image/png,image/jpeg,image/webp" class="d-none">

                <p class="text-muted small mb-0">
                    اندازه پیشنهادی ۱۹۲۰ در ۶۰۰ پیکسل — فرمت‌های مجاز: jpg، png، webp — حداکثر ۴ مگابایت
                    @if ($slider) <br>برای تغییر بنر، روی تصویر کلیک کنید. @endif
                </p>
                @error('image') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-4">اطلاعات اسلایدر</h6>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">عنوان <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="type"></i></span>
                            <input type="text" id="title" wire:model="title" class="form-control @error('title') is-invalid @enderror" placeholder="عنوان اسلایدر">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="link" class="form-label">لینک</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="link"></i></span>
                            <input type="text" id="link" wire:model="link" dir="ltr" class="form-control text-start @error('link') is-invalid @enderror" placeholder="https://... یا /posts/...">
                            @error('link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">اختیاری — با کلیک روی بنر به این آدرس می‌رود.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="sort_order" class="form-label">ترتیب نمایش <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="list"></i></span>
                            <input type="number" id="sort_order" wire:model="sort_order" min="0" dir="ltr" class="form-control text-start @error('sort_order') is-invalid @enderror">
                            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">عدد کوچک‌تر زودتر نمایش داده می‌شود.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label d-block">وضعیت</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" wire:model.live="is_active">
                            <label class="form-check-label" for="is_active">{{ $is_active ? 'فعال — در سایت نمایش داده می‌شود' : 'غیرفعال — در سایت نمایش داده نمی‌شود' }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button type="submit" class="btn btn-primary btn-icon-text" wire:loading.attr="disabled" wire:target="save, image">
                <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                <span wire:loading.remove wire:target="save">{{ $slider ? 'ذخیره تغییرات' : 'ثبت اسلایدر' }}</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
            <a href="{{ route('sliders.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

