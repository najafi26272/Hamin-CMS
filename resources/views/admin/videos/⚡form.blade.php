<?php

use App\Models\Video;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public ?Video $video = null;

    public string $title = '';
    public string $slug = '';
    public string $description = '';

    public $videoFile = null;              // فایل ویدیوی جدید
    public $thumbnail = null;              // تصویر اصلی جدید
    public bool $removeThumbnail = false;  // حذف تصویر اصلی فعلی با ذخیره

    // اگر کاربر slug را دستی تغییر دهد، دیگر از روی عنوان ساخته نمی‌شود
    public bool $slugTouched = false;

    public function mount(Video $video): void
    {
        if (! $video->exists) {
            return;
        }

        $this->video = $video;

        $this->title = $video->title;
        $this->slug = $video->slug;
        $this->description = $video->description ?? '';

        // در ویرایش، slug قبلی حفظ می‌شود تا آدرس‌ها تغییر نکنند
        $this->slugTouched = true;
    }

    public function updatedTitle(): void
    {
        if (! $this->slugTouched) {
            $this->slug = Video::uniqueSlug($this->title, $this->video?->id);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    // دکمه «ساخت از روی عنوان»
    public function generateSlug(): void
    {
        $this->slug = Video::uniqueSlug($this->title, $this->video?->id);
        $this->slugTouched = false;
        $this->resetValidation('slug');
    }

    public function updatedVideoFile(): void
    {
        $this->validateOnly('videoFile');
    }

    public function updatedThumbnail(): void
    {
        $this->validateOnly('thumbnail');
        $this->removeThumbnail = false;
    }

    // دکمه حذف تصویر اصلی
    public function clearThumbnail(): void
    {
        $this->thumbnail = null;
        $this->removeThumbnail = true;
        $this->resetValidation('thumbnail');
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'max:190', 'regex:/^[\p{L}\p{N}_]+$/u', Rule::unique('videos', 'slug')->ignore($this->video?->id)],
            'description' => ['nullable', 'string'],
            'videoFile' => [$this->video ? 'nullable' : 'required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:204800'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'slug.regex' => 'slug فقط می‌تواند شامل حروف، عدد و _ باشد.',
            'videoFile.required' => 'فایل ویدیو را انتخاب کنید.',
            'videoFile.mimetypes' => 'فرمت ویدیو باید mp4، webm یا mov باشد.',
            'videoFile.max' => 'حجم ویدیو نباید بیشتر از ۲۰۰ مگابایت باشد.',
            'videoFile.uploaded' => 'آپلود ویدیو انجام نشد. حجم فایل از حد مجاز سرور بیشتر است.',
            'thumbnail.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'thumbnail.mimes' => 'فرمت تصویر باید jpg، png یا webp باشد.',
            'thumbnail.max' => 'حجم تصویر نباید بیشتر از ۴ مگابایت باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'عنوان',
            'slug' => 'slug',
            'description' => 'توضیحات',
            'videoFile' => 'فایل ویدیو',
            'thumbnail' => 'تصویر اصلی',
        ];
    }

    public function save()
    {
        // slug دستی هم با همان قوانین استاندارد اصلاح می‌شود
        $this->slug = Video::makeSlug($this->slug);

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
        ];

        // فایل‌های قبلی فقط بعد از ذخیره موفق پاک می‌شوند
        $oldFiles = [];

        try {
            if ($this->videoFile) {
                $data['video_path'] = $this->videoFile->store('videos', 'public');
                $oldFiles[] = $this->video?->video_path;
            }

            if ($this->thumbnail || $this->removeThumbnail) {
                $data['thumbnail'] = $this->thumbnail ? $this->thumbnail->store('videos/thumbnails', 'public') : null;
                $oldFiles[] = $this->video?->thumbnail;
            }

            if ($this->video) {
                $this->video->update($data);
                $message = 'فیلم با موفقیت ویرایش شد.';
            } else {
                Video::create($data + ['user_id' => auth()->id()]);
                $message = 'فیلم جدید با موفقیت ثبت شد.';
            }

            Storage::disk('public')->delete(array_filter($oldFiles));
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('videos.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $video ? 'ویرایش فیلم: '.\Illuminate\Support\Str::limit($video->title, 50) : 'ثبت فیلم جدید' }}</h5>

        <a href="{{ route('videos.index') }}" class="btn btn-outline-secondary btn-icon-text">
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
                        <h6 class="card-title mb-4">اطلاعات فیلم</h6>

                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="type"></i></span>
                                <input type="text" id="title" wire:model.live.debounce.400ms="title"
                                       class="form-control @error('title') is-invalid @enderror" placeholder="عنوان فیلم">
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

                        <div class="mb-1">
                            <label class="form-label">توضیحات</label>
                            <div wire:ignore>
                                <div id="description-editor"></div>
                            </div>
                            @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- فایل ویدیو --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">فایل ویدیو @unless ($video)<span class="text-danger">*</span>@endunless</h6>

                        @if ($videoFile && ! $errors->has('videoFile'))
                            @if ($videoFile->isPreviewable())
                                <video src="{{ $videoFile->temporaryUrl() }}" controls class="video-preview mb-2"></video>
                            @endif
                            <div class="text-success small mb-2">
                                فایل جدید: <span dir="ltr">{{ $videoFile->getClientOriginalName() }}</span>
                                ({{ round($videoFile->getSize() / 1048576, 1) }} MB)
                            </div>
                        @elseif ($video)
                            <video src="{{ $video->video_url }}" controls class="video-preview mb-2"></video>
                        @endif

                        <div x-data="{ uploading: false, progress: 0 }"
                             x-on:livewire-upload-start="uploading = true; progress = 0"
                             x-on:livewire-upload-progress="progress = $event.detail.progress"
                             x-on:livewire-upload-finish="uploading = false"
                             x-on:livewire-upload-cancel="uploading = false"
                             x-on:livewire-upload-error="uploading = false">

                            <label for="videoFile" class="video-dropzone" x-show="! uploading">
                                <span class="text-center">
                                    <span wire:ignore><i data-feather="film" class="mb-2"></i></span><br>
                                    {{ $video || $videoFile ? 'برای جایگزینی فایل ویدیو کلیک کنید' : 'برای انتخاب فایل ویدیو کلیک کنید' }}<br>
                                    <small class="text-muted">فرمت‌های مجاز: mp4، webm، mov — حداکثر ۲۰۰ مگابایت</small>
                                </span>
                            </label>
                            <input type="file" id="videoFile" wire:model="videoFile" accept="video/mp4,video/webm,video/quicktime" class="d-none">

                            {{-- نوار پیشرفت آپلود --}}
                            <div x-show="uploading" x-cloak>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>در حال آپلود ویدیو...</span>
                                    <span x-text="progress + '%'"></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" x-bind:style="'width: ' + progress + '%'"></div>
                                </div>
                            </div>
                        </div>

                        @error('videoFile') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- ستون کناری --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">تصویر اصلی (کاور)</h6>
                        @php($thumbPreview = $thumbnail && ! $errors->has('thumbnail') ? $thumbnail->temporaryUrl() : ($removeThumbnail ? null : $video?->thumbnail_url))

                        <div class="position-relative">
                            <label for="thumbnail" class="thumbnail-upload">
                                @if ($thumbPreview)
                                    <img src="{{ $thumbPreview }}" alt="تصویر اصلی">
                                @else
                                    <span class="text-muted text-center small">
                                        <span wire:ignore><i data-feather="image" class="mb-1"></i></span><br>
                                        انتخاب تصویر اصلی
                                    </span>
                                @endif

                                <div class="thumbnail-loading" wire:loading.flex wire:target="thumbnail">
                                    <div class="spinner-border text-light" role="status"></div>
                                </div>
                            </label>

                            @if ($thumbPreview)
                                <button type="button" class="btn btn-danger btn-sm thumbnail-remove" title="حذف تصویر" wire:click="clearThumbnail">
                                    <span wire:ignore><i data-feather="x"></i></span>
                                </button>
                            @endif
                        </div>
                        <input type="file" id="thumbnail" wire:model="thumbnail" accept="image/png,image/jpeg,image/webp" class="d-none">

                        <p class="text-muted small mt-2 mb-0">فرمت‌های مجاز: jpg، png، webp — حداکثر ۴ مگابایت</p>
                        @error('thumbnail') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-icon-text w-100 mb-2" wire:loading.attr="disabled" wire:target="save, videoFile, thumbnail">
                    <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                    <span wire:loading.remove wire:target="save">{{ $video ? 'ذخیره تغییرات' : 'ثبت فیلم' }}</span>
                    <span wire:loading wire:target="save">در حال ذخیره...</span>
                </button>
                <a href="{{ route('videos.index') }}" class="btn btn-secondary w-100 mb-3">انصراف</a>
            </div>
        </div>
    </form>
</div>

@assets
<script src="{{ asset('assets/admin/vendors/ckeditor5/ckeditor5.umd.js') }}"></script>
<script src="{{ asset('assets/admin/vendors/ckeditor5/translations/fa.umd.js') }}"></script>
<style>
    [x-cloak] {
        display: none !important;
    }

    .video-preview {
        width: 100%;
        max-height: 360px;
        border-radius: 8px;
        background: #000;
    }

    .video-dropzone {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 120px;
        padding: 1rem;
        border: 2px dashed var(--bs-border-color);
        border-radius: 8px;
        cursor: pointer;
        margin: 0;
    }

    .video-dropzone:hover {
        border-color: #6571ff;
    }

    .thumbnail-upload {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        aspect-ratio: 16 / 9;
        border: 2px dashed var(--bs-border-color);
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        margin: 0;
    }

    .thumbnail-upload img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .thumbnail-loading {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        align-items: center;
        justify-content: center;
    }

    .thumbnail-remove {
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

    .thumbnail-remove svg {
        width: 16px;
        height: 16px;
    }

    .ck.ck-editor__editable_inline {
        min-height: 200px;
    }
</style>
@endassets

@script
<script>
    // ویرایشگر متن (CKEditor 5 با امکان راست‌چین، وسط‌چین و چپ‌چین)
    createEditor(document.querySelector('#description-editor'), $wire, 'description');
</script>
@endscript