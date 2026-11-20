<?php

use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public ?Gallery $gallery = null;

    public string $title = '';
    public string $slug = '';
    public string $description = '';

    public $thumbnail = null;              // تصویر شاخص جدید
    public bool $removeThumbnail = false;  // حذف تصویر شاخص فعلی با ذخیره

    public $uploads = [];             // فایل‌های انتخاب شده در هر بار انتخاب
    public array $newImages = [];     // تصاویر جدید (هنوز ذخیره نشده)
    public array $newCaptions = [];   // توضیح تصاویر جدید (هم‌اندیس با newImages)
    public array $captions = [];      // توضیح تصاویر فعلی [شناسه => توضیح]
    public array $removedImageIds = []; // تصاویر فعلی که با ذخیره حذف می‌شوند

    // اگر کاربر slug را دستی تغییر دهد، دیگر از روی عنوان ساخته نمی‌شود
    public bool $slugTouched = false;

    public function mount(Gallery $gallery): void
    {
        if (! $gallery->exists) {
            return;
        }

        $this->gallery = $gallery;

        $this->title = $gallery->title;
        $this->slug = $gallery->slug;
        $this->description = $gallery->description ?? '';
        $this->captions = $gallery->images->mapWithKeys(fn ($image) => [$image->id => $image->caption ?? ''])->all();

        // در ویرایش، slug قبلی حفظ می‌شود تا آدرس‌ها تغییر نکنند
        $this->slugTouched = true;
    }

    // تصاویر فعلی گالری به جز موارد حذف شده
    #[Computed]
    public function existingImages()
    {
        return $this->gallery
            ? $this->gallery->images()->whereNotIn('id', $this->removedImageIds)->get()
            : collect();
    }

    public function updatedTitle(): void
    {
        if (! $this->slugTouched) {
            $this->slug = Gallery::uniqueSlug($this->title, $this->gallery?->id);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    // دکمه «ساخت از روی عنوان»
    public function generateSlug(): void
    {
        $this->slug = Gallery::uniqueSlug($this->title, $this->gallery?->id);
        $this->slugTouched = false;
        $this->resetValidation('slug');
    }

    public function updatedThumbnail(): void
    {
        $this->validateOnly('thumbnail');
        $this->removeThumbnail = false;
    }

    // دکمه حذف تصویر شاخص
    public function clearThumbnail(): void
    {
        $this->thumbnail = null;
        $this->removeThumbnail = true;
        $this->resetValidation('thumbnail');
    }

    // تصاویر انتخاب شده به لیست تصاویر جدید اضافه می‌شوند
    public function updatedUploads(): void
    {
        $this->validate(
            ['uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096']],
            [
                'uploads.*.image' => 'فایل انتخاب شده باید تصویر باشد.',
                'uploads.*.mimes' => 'فرمت تصاویر باید jpg، png یا webp باشد.',
                'uploads.*.max' => 'حجم هر تصویر نباید بیشتر از ۴ مگابایت باشد.',
            ]
        );

        foreach ($this->uploads as $file) {
            $this->newImages[] = $file;
            $this->newCaptions[] = '';
        }

        $this->uploads = [];
    }

    public function removeNewImage(int $index): void
    {
        array_splice($this->newImages, $index, 1);
        array_splice($this->newCaptions, $index, 1);
    }

    public function removeImage(int $id): void
    {
        $this->removedImageIds[] = $id;
        unset($this->captions[$id]);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'max:190', 'regex:/^[\p{L}\p{N}_]+$/u', Rule::unique('galleries', 'slug')->ignore($this->gallery?->id)],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'newImages.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'newCaptions.*' => ['nullable', 'string', 'max:190'],
            'captions.*' => ['nullable', 'string', 'max:190'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'slug.regex' => 'slug فقط می‌تواند شامل حروف، عدد و _ باشد.',
            'thumbnail.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'thumbnail.mimes' => 'فرمت تصویر شاخص باید jpg، png یا webp باشد.',
            'thumbnail.max' => 'حجم تصویر شاخص نباید بیشتر از ۴ مگابایت باشد.',
            'newImages.*.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'newImages.*.mimes' => 'فرمت تصاویر باید jpg، png یا webp باشد.',
            'newImages.*.max' => 'حجم هر تصویر نباید بیشتر از ۴ مگابایت باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'عنوان',
            'slug' => 'slug',
            'description' => 'توضیحات',
            'thumbnail' => 'تصویر شاخص',
            'newCaptions.*' => 'توضیح تصویر',
            'captions.*' => 'توضیح تصویر',
        ];
    }

    public function save()
    {
        // slug دستی هم با همان قوانین استاندارد اصلاح می‌شود
        $this->slug = Gallery::makeSlug($this->slug);

        try {
            $this->validate();

            // گالری باید حداقل یک تصویر داشته باشد
            if ($this->existingImages->count() + count($this->newImages) === 0) {
                throw ValidationException::withMessages(['newImages' => 'حداقل یک تصویر برای گالری انتخاب کنید.']);
            }
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'title' => trim($this->title),
            'slug' => $this->slug,
            'description' => HtmlSanitizer::clean($this->description) ?: null,
        ];

        try {
            // تصویر شاخص: جایگزینی یا حذف
            $oldThumbnail = $this->gallery?->thumbnail;
            $thumbnailChanged = $this->thumbnail || $this->removeThumbnail;

            if ($thumbnailChanged) {
                $data['thumbnail'] = $this->thumbnail ? $this->thumbnail->store('galleries/thumbnails', 'public') : null;
            }

            if ($this->gallery) {
                $this->gallery->update($data);
                $gallery = $this->gallery;
                $message = 'گالری با موفقیت ویرایش شد.';
            } else {
                $gallery = Gallery::create($data + ['user_id' => auth()->id()]);
                $message = 'گالری جدید با موفقیت ثبت شد.';
            }

            // فایل تصویر شاخص قبلی بعد از ذخیره موفق پاک می‌شود
            if ($thumbnailChanged && $oldThumbnail) {
                Storage::disk('public')->delete($oldThumbnail);
            }

            // حذف تصاویری که کاربر برداشته است
            $removed = $gallery->images()->whereIn('id', $this->removedImageIds)->get();
            foreach ($removed as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }

            // ذخیره توضیح تصاویر فعلی
            foreach ($this->captions as $id => $caption) {
                $gallery->images()->whereKey($id)->update(['caption' => trim($caption) ?: null]);
            }

            // ذخیره تصاویر جدید در انتهای گالری
            $order = (int) $gallery->images()->max('sort_order');
            foreach ($this->newImages as $index => $file) {
                $gallery->images()->create([
                    'path' => $file->store('galleries/'.$gallery->id, 'public'),
                    'caption' => trim($this->newCaptions[$index] ?? '') ?: null,
                    'sort_order' => ++$order,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('galleries.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $gallery ? 'ویرایش گالری: '.\Illuminate\Support\Str::limit($gallery->title, 50) : 'ثبت گالری جدید' }}</h5>

        <a href="{{ route('galleries.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit="save" novalidate>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-4">اطلاعات گالری</h6>

                <div class="row">
                    {{-- تصویر شاخص --}}
                    <div class="col-md-4 col-xl-3 mb-3 order-md-last">
                        <label class="form-label">تصویر شاخص (کاور)</label>
                        @php($thumbPreview = $thumbnail && ! $errors->has('thumbnail') ? $thumbnail->temporaryUrl() : ($removeThumbnail ? null : $gallery?->thumbnail_url))

                        <div class="position-relative">
                            <label for="thumbnail" class="thumbnail-upload">
                                @if ($thumbPreview)
                                    <img src="{{ $thumbPreview }}" alt="تصویر شاخص">
                                @else
                                    <span class="text-muted text-center small">
                                        <span wire:ignore><i data-feather="image" class="mb-1"></i></span><br>
                                        انتخاب تصویر شاخص
                                    </span>
                                @endif

                                <div class="thumbnail-loading" wire:loading.flex wire:target="thumbnail">
                                    <div class="spinner-border text-light" role="status"></div>
                                </div>
                            </label>

                            @if ($thumbPreview)
                                <button type="button" class="btn btn-danger btn-sm gallery-remove" title="حذف تصویر شاخص" wire:click="clearThumbnail">
                                    <span wire:ignore><i data-feather="x"></i></span>
                                </button>
                            @endif
                        </div>
                        <input type="file" id="thumbnail" wire:model="thumbnail" accept="image/png,image/jpeg,image/webp" class="d-none">

                        <div class="form-text">اگر انتخاب نشود، اولین تصویر گالری به عنوان کاور نمایش داده می‌شود.</div>
                        @error('thumbnail') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-8 col-xl-9">
                    <div class="row">
                    <div class="col-xl-6 mb-3">
                        <label for="title" class="form-label">عنوان <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="type"></i></span>
                            <input type="text" id="title" wire:model.live.debounce.400ms="title"
                                   class="form-control @error('title') is-invalid @enderror" placeholder="عنوان گالری">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-xl-6 mb-3">
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

                    <div class="col-12 mb-1">
                        <label class="form-label">توضیحات</label>
                        <div wire:ignore>
                            <div id="description-editor"></div>
                        </div>
                        @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title mb-0">تصاویر <span class="text-danger">*</span></h6>
                    <span class="text-muted small">{{ $this->existingImages->count() + count($newImages) }} تصویر</span>
                </div>

                {{-- انتخاب تصاویر --}}
                <label for="uploads" class="gallery-dropzone mb-3">
                    <span class="text-center" wire:loading.remove wire:target="uploads">
                        <span wire:ignore><i data-feather="upload-cloud" class="mb-2"></i></span><br>
                        برای انتخاب تصاویر کلیک کنید (امکان انتخاب چند تصویر با هم)<br>
                        <small class="text-muted">فرمت‌های مجاز: jpg، png، webp — حداکثر ۴ مگابایت برای هر تصویر</small>
                    </span>
                    <span wire:loading wire:target="uploads">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span> در حال آپلود...
                    </span>
                </label>
                <input type="file" id="uploads" wire:model="uploads" multiple accept="image/png,image/jpeg,image/webp" class="d-none">

                @error('uploads.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                @error('newImages') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
                @error('newImages.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                <div class="row g-3">
                    {{-- تصاویر فعلی --}}
                    @foreach ($this->existingImages as $image)
                        <div class="col-6 col-md-4 col-xl-3" wire:key="image-{{ $image->id }}">
                            <div class="gallery-item">
                                <img src="{{ $image->url }}" alt="">
                                <button type="button" class="btn btn-danger btn-sm gallery-remove" title="حذف تصویر"
                                        wire:click="removeImage({{ $image->id }})">
                                    <span wire:ignore><i data-feather="x"></i></span>
                                </button>
                            </div>
                            <input type="text" wire:model="captions.{{ $image->id }}"
                                   class="form-control form-control-sm mt-2 @error('captions.'.$image->id) is-invalid @enderror" placeholder="توضیح تصویر (اختیاری)">
                            @error('captions.'.$image->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endforeach

                    {{-- تصاویر جدید --}}
                    @foreach ($newImages as $index => $file)
                        <div class="col-6 col-md-4 col-xl-3" wire:key="new-image-{{ $index }}-{{ $file->getFilename() }}">
                            <div class="gallery-item">
                                <img src="{{ $file->temporaryUrl() }}" alt="">
                                <span class="badge bg-success gallery-new">جدید</span>
                                <button type="button" class="btn btn-danger btn-sm gallery-remove" title="حذف تصویر"
                                        wire:click="removeNewImage({{ $index }})">
                                    <span wire:ignore><i data-feather="x"></i></span>
                                </button>
                            </div>
                            <input type="text" wire:model="newCaptions.{{ $index }}"
                                   class="form-control form-control-sm mt-2 @error('newCaptions.'.$index) is-invalid @enderror" placeholder="توضیح تصویر (اختیاری)">
                            @error('newCaptions.'.$index) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>

                @if ($removedImageIds)
                    <div class="text-warning small mt-3">{{ count($removedImageIds) }} تصویر با ذخیره تغییرات حذف خواهد شد.</div>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button type="submit" class="btn btn-primary btn-icon-text" wire:loading.attr="disabled" wire:target="save, uploads, thumbnail">
                <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                <span wire:loading.remove wire:target="save">{{ $gallery ? 'ذخیره تغییرات' : 'ثبت گالری' }}</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
            <a href="{{ route('galleries.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

@assets
<script src="{{ asset('assets/admin/vendors/ckeditor5/ckeditor5.umd.js') }}"></script>
<script src="{{ asset('assets/admin/vendors/ckeditor5/translations/fa.umd.js') }}"></script>
@endassets

@script
<script>
    // ویرایشگر متن (CKEditor 5 با امکان راست‌چین، وسط‌چین و چپ‌چین)
    createEditor(document.querySelector('#description-editor'), $wire, 'description');
</script>
@endscript