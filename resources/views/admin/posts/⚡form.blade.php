<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    public ?Post $post = null;

    public string $type = '';            // نوع محتوا (دسته‌بندی اصلی): news, visual, audio, video, poem
    public string $title = '';
    public string $slug = '';
    public string $creator_name = '';    // نام صاحب اثر / سازنده / شاعر
    public string $user_id = '';         // ثبت‌کننده‌ی مطلب (قابل انتخاب توسط مدیر/نویسنده)؛ ثبت‌کننده‌ی واقعی در main_user_id ذخیره می‌شود
    public string $content = '';
    public string $status = 'draft';
    public ?int $published_at = null;    // unix timestamp (ثانیه)؛ در تقویم به صورت شمسی و ساعت تهران نمایش داده می‌شود
    public $image = null;                // پوستر / تصویر شاخص جدید
    public $mediaFile = null;            // فایل جدید (تصویر، صوت یا ویدیو)
    public $mainFile = null;             // فایل با حجم اصلی جدید (از طریق FTP ذخیره می‌شود)
    public array $categories = [];       // فقط برای اخبار
    public array $tags = [];
    public $file_link = '';
    public $aparat_hash = '';

    // اگر کاربر slug را دستی تغییر دهد، دیگر از روی عنوان ساخته نمی‌شود
    public bool $slugTouched = false;

    public function mount(Post $post): void
    {


        if (!$post->exists) {
            // نوع محتوا از آدرس خوانده می‌شود: /posts/create?type=poem
            $type = (string)request()->query('type', '');
            $allowed = auth()->user()->allowedPostTypes();

            if (count($allowed) === 1) {
                $type = $allowed[0];
            }

            $this->type = in_array($type, $allowed) ? $type : '';
            $this->status = auth()->user()->canPublishDirectly() ? 'draft' : 'pending';
            $this->user_id = (string)auth()->id();

            return;
        }

        $this->post = $post;

        $this->type = $post->type;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->creator_name = $post->creator_name ?? '';
        $this->user_id = (string)$post->user_id;
        $this->content = $post->content ?? '';
        $this->status = $post->status;
        $this->file_link = $post->file_link;
        $this->aparat_hash = filled($post->aparat_hash)
            ? 'https://www.aparat.com/v/' . $post->aparat_hash
            : '';
        $this->published_at = $post->published_at?->timestamp;
        $this->categories = $post->categories()->pluck('categories.id')->map(fn($id) => (string)$id)->all();
        $this->tags = $post->tags()->pluck('tags.id')->map(fn($id) => (string)$id)->all();

        // در ویرایش، slug قبلی حفظ می‌شود تا آدرس‌ها تغییر نکنند
        $this->slugTouched = true;
    }



    private function extractAparatHash(?string $url): ?string
    {
        $url = trim($url ?? '');

        $pattern = '~^https?://(?:www\.)?aparat\.com/v/([a-zA-Z0-9]+)/?$~i';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }


    // ---------- اطلاعات کمکی ----------

    #[Computed]
    public function typeInfo(): array
    {
        return Post::TYPES[$this->type] ?? Post::TYPES['news'];
    }

    #[Computed]
    public function allowedTypes(): array
    {
        return auth()->user()->allowedPostTypes();
    }

    // مدیر و نویسنده وضعیت انتشار را انتخاب می‌کنند؛ مطالب هنرمندان باید بررسی شود
    #[Computed]
    public function isStaff(): bool
    {
        return auth()->user()->canPublishDirectly();
    }

    #[Computed]
    public function canReview(): bool
    {
        return $this->post && $this->post->canBeApproved() && Gate::allows('review-content');
    }

    // آپلود فایل با حجم اصلی فقط وقتی فعال است که دیسک ftp در .env تنظیم شده باشد
    #[Computed]
    public function ftpEnabled(): bool
    {
        return filled(config('filesystems.disks.ftp.host'));
    }

    // فهرست کاربران برای انتخاب ثبت‌کننده (فقط مدیر و نویسنده)
    #[Computed]
    public function userOptions()
    {
        return User::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return Category::treeOptions();
    }

    #[Computed]
    public function allTags()
    {
        return Tag::orderBy('name')->get(['id', 'name']);
    }

    // ---------- slug ----------

    public function updatedTitle(): void
    {
        if (!$this->slugTouched) {
            $this->slug = Post::uniqueSlug($this->title, $this->post?->id);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    public function generateSlug(): void
    {
        $this->slug = Post::uniqueSlug($this->title, $this->post?->id);
        $this->slugTouched = false;
        $this->resetValidation('slug');
    }

    // ---------- فایل‌ها ----------

    public function updatedImage(): void
    {
        $this->validateOnly('image');
    }

    public function updatedMediaFile(): void
    {
        $this->validateOnly('mediaFile');
    }

    public function updatedMainFile(): void
    {
        $this->validateOnly('mainFile');
    }

    // فایل با حجم اصلی (اختیاری): سقف حجم برحسب کیلوبایت، پیش‌فرض ۱ گیگابایت
    protected function mainFileRules(): array
    {
        $extensions = match ($this->typeInfo['media']) {
            'image' => 'jpg,jpeg,png,webp,tif,tiff',
            'audio' => 'mp3,wav,flac,ogg,m4a,aac',
            'video' => 'mp4,webm,mov,mkv',
            default => null,
        };

        return $extensions
            ? ['nullable', 'file', 'extensions:' . $extensions, 'max:1048576']
            : [];
    }

    protected function mediaRules(): array
    {
        return match ($this->typeInfo['media']) {
            'image' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'audio' => ['file', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/x-m4a,audio/aac', 'max:51200'],
            'video' => ['file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:204800'],
            default => [],
        };
    }

    // ---------- اعتبارسنجی ----------

    protected function rules(): array
    {
        $config = $this->typeInfo;

        $rules = [
            'title' => ['required', 'string', 'max:190'],
            'file_link' => ['nullable', 'regex:/^https?:\/\/\S+$/i', 'max:500'],
            'slug' => ['required', 'max:190', 'regex:/^[\p{L}\p{N}_]+$/u', Rule::unique('posts', 'slug')->ignore($this->post?->id)],
            'content' => [$config['content_required'] ? 'required' : 'nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'tags' => ['nullable', 'array'],
            'tags.*' => [Rule::exists('tags', 'id')],
        ];

        if ($config['creator']) {
            $rules['creator_name'] = ['required', 'string', 'max:190'];
        }

        if ($config['media']) {

            $isVideo = $config['media'] === 'video';
            $hasOldMedia = (bool) $this->post?->media_file;
            $hasAparat = $isVideo && filled(trim($this->aparat_hash));

            // در ویدیو، یا فایل ویدیو لازم است یا کد آپارات (یکی کافی است)
            // در ویرایش، اگر فایل قبلی وجود دارد، انتخاب فایل جدید اختیاری است
            $rules['mediaFile'] = [
                ($hasOldMedia || $hasAparat) ? 'nullable' : 'required',
                ...$this->mediaRules(),
            ];

            if ($isVideo) {
                $rules['aparat_hash'] = [
                    ($hasOldMedia || $this->mediaFile) ? 'nullable' : 'required',
                    'regex:/^https?:\/\/\S+$/i',
                    'max:1000',
                ];
            }

        }

        if ($config['media'] && $this->ftpEnabled) {
            $rules['mainFile'] = $this->mainFileRules();
        }

        if ($this->type === 'news') {
            $rules['categories'] = ['nullable', 'array'];
            $rules['categories.*'] = [Rule::exists('categories', 'id')];
        }

        if ($this->isStaff) {
            // وضعیت فعلی مطلب (مثلا در انتظار بررسی) بدون تغییر هم قابل ذخیره است
            $rules['status'] = ['required', Rule::in(array_unique(['draft', 'published', $this->post?->status ?? 'draft']))];
            $rules['published_at'] = ['nullable', 'integer'];
            $rules['user_id'] = ['required', Rule::exists('users', 'id')];
        }

        return $rules;
    }

    protected function messages(): array
    {
        $mediaFormats = match ($this->typeInfo['media']) {
            'image' => 'jpg، png یا webp تا ۱۰ مگابایت',
            'audio' => 'mp3، wav، ogg یا m4a تا ۵۰ مگابایت',
            'video' => 'mp4، webm یا mov تا ۲۰۰ مگابایت',
            default => '',
        };

        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'exists' => ':attribute انتخاب شده معتبر نیست.',
            'in' => ':attribute انتخاب شده معتبر نیست.',
            'integer' => ':attribute معتبر نیست.',
            'file_link.regex' => 'آدرس باید کامل و با http:// یا https:// شروع شود.',
            'file_link.max' => 'آدرس نباید بیشتر از ۵۰۰ کاراکتر باشد.',
            'aparat_hash.regex' => 'لینک آپارات باید کامل و با http:// یا https:// شروع شود.',
            'aparat_hash.max' => 'لینک آپارات نباید بیشتر از 1000 کاراکتر باشد.',
            'user_id.required' => 'ثبت‌کننده را انتخاب کنید.',
            'slug.regex' => 'slug فقط می‌تواند شامل حروف، عدد و _ باشد.',
            'image.image' => 'فایل پوستر باید تصویر باشد.',
            'image.mimes' => 'فرمت پوستر باید jpg، png یا webp باشد.',
            'image.max' => 'حجم پوستر نباید بیشتر از ۴ مگابایت باشد.',
            'mediaFile.required' => $this->typeInfo['media'] === 'video'
                ? 'فایل ویدیو را انتخاب کنید یا کد (لینک) آپارات را وارد کنید.'
                : ':attribute را انتخاب کنید.',
            'aparat_hash.required' => 'لینک آپارات را وارد کنید یا فایل ویدیو را انتخاب کنید.',
            'mediaFile.image' => 'فرمت :attribute معتبر نیست (' . $mediaFormats . ').',
            'mediaFile.mimes' => 'فرمت :attribute معتبر نیست (' . $mediaFormats . ').',
            'mediaFile.mimetypes' => 'فرمت :attribute معتبر نیست (' . $mediaFormats . ').',
            'mediaFile.max' => 'حجم :attribute بیشتر از حد مجاز است (' . $mediaFormats . ').',
            'mediaFile.uploaded' => 'آپلود :attribute انجام نشد. حجم فایل از حد مجاز سرور بیشتر است.',
            'mainFile.extensions' => 'فرمت :attribute معتبر نیست.',
            'mainFile.max' => 'حجم :attribute بیشتر از حد مجاز است (حداکثر ۱ گیگابایت).',
            'mainFile.uploaded' => 'آپلود :attribute انجام نشد. حجم فایل از حد مجاز سرور بیشتر است.',
        ];
    }

    protected function validationAttributes(): array
    {
        $config = $this->typeInfo;

        return [
            'title' => 'عنوان',
            'file_link' => 'آدرس فایل',
            'aparat_hash' => 'لینک آپارات',
            'slug' => 'slug',
            'creator_name' => $config['creator'] ?? 'نام صاحب اثر',
            'content' => $config['content_label'],
            'mediaFile' => $config['media_label'] ?? 'فایل',
            'image' => $config['poster_label'],
            'mainFile' => 'فایل با حجم اصلی',
            'status' => 'وضعیت انتشار',
            'user_id' => 'ثبت‌کننده',
            'published_at' => 'تاریخ انتشار',
            'categories' => 'دسته‌بندی‌ها',
            'categories.*' => 'دسته‌بندی',
            'tags' => 'برچسب‌ها',
            'tags.*' => 'برچسب',
        ];
    }

    // ---------- ذخیره ----------

    public function save()
    {
        $allowed = $this->post
            ? Gate::allows('update-post', $this->post)
            : Gate::allows('create-post') && in_array($this->type, $this->allowedTypes);

        if (!$allowed) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی لازم برای این عملیات را ندارید.');
            return null;
        }

        // slug دستی هم با همان قوانین استاندارد اصلاح می‌شود
        $this->slug = Post::makeSlug($this->slug);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'type' => $this->type,
            'title' => trim($this->title),
            'file_link' => trim($this->file_link) ?: null,
            'aparat_hash' => filled($this->aparat_hash)
                ? $this->extractAparatHash($this->aparat_hash)
                : null,
            'slug' => $this->slug,
            'creator_name' => $this->typeInfo['creator'] ? trim($this->creator_name) : null,
            'content' => HtmlSanitizer::clean($this->content) ?: null,
        ];

        if ($this->isStaff) {
            $publishedAt = $this->published_at ? Carbon::createFromTimestamp($this->published_at) : null;

            // مطلب منتشر شده بدون تاریخ، همین الان منتشر می‌شود
            if ($this->status === 'published' && !$publishedAt) {
                $publishedAt = now();
            }

            $data['status'] = $this->status;
            $data['published_at'] = $publishedAt;
            // ثبت‌کننده‌ی انتخاب‌شده (بعد از اعتبارسنجی)
            $data['user_id'] = (int)$this->user_id;
        } else {
            // مطلب هنرمند (جدید یا ویرایش شده) دوباره برای بررسی ارسال می‌شود
            $data['status'] = 'pending';
            $data['published_at'] = null;
            $data['rejection_reason'] = null;
        }

        // انتقال فایل با حجم اصلی به سرور FTP (پیش از ذخیره در دیتابیس)
        $newMainFile = null;
        $oldMainFile = $this->post?->main_file;

        if ($this->mainFile && $this->typeInfo['media'] && $this->ftpEnabled) {
            try {
                set_time_limit(0);

                $newMainFile = $this->mainFile->storeAs(
                    'main/' . $this->type,
                    Str::uuid() . '.' . strtolower($this->mainFile->getClientOriginalExtension()),
                    ['disk' => 'ftp']
                );

                $data['main_file'] = $newMainFile;
                $data['main_file_name'] = Str::limit($this->mainFile->getClientOriginalName(), 190, '');
                $data['media_file_main_size'] = $this->mainFile->getSize();
            } catch (\Throwable $e) {
                report($e);
                $this->dispatch('notify', type: 'error', message: 'انتقال فایل به سرور FTP انجام نشد. اتصال FTP را بررسی کنید.');
                return null;
            }
        }

        $oldFiles = [];

        try {
            if ($this->image) {
                $data['image'] = $this->image->store('posts', 'public');
                $oldFiles[] = $this->post?->image;
            }

            if ($this->mediaFile && $this->typeInfo['media']) {
                $data['media_file'] = $this->mediaFile->store('posts/media/' . $this->type, 'public');
                $oldFiles[] = $this->post?->media_file;
            }

            if ($this->post) {
                $this->post->update($data);
                $post = $this->post;
            } else {
                // user_id: کاربر انتخاب‌شده (برای غیرمدیر همیشه خودش)؛ main_user_id: کاربری که واقعا مطلب را ثبت کرده
                // (اگر مدیر user_id را انتخاب کرده باشد، $data['user_id'] در + حفظ می‌شود)
                $post = Post::create($data + [
                        'user_id' => auth()->id(),
                        'main_user_id' => auth()->id(),
                    ]);
            }

            $post->categories()->sync($this->type === 'news' ? $this->categories : []);
            $post->tags()->sync($this->tags);

            // فایل‌های قبلی فقط بعد از ذخیره موفق پاک می‌شوند
            Storage::disk('public')->delete(array_filter($oldFiles));

            // فایل قبلی روی FTP فقط بعد از ذخیره موفق پاک می‌شود (خطای حذف ذخیره را خراب نمی‌کند)
            if ($newMainFile && $oldMainFile) {
                try {
                    Storage::disk('ftp')->delete($oldMainFile);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } catch (\Throwable $e) {
            report($e);

            // اگر ذخیره ناموفق بود، فایلی که تازه روی FTP رفته پاک شود
            if ($newMainFile) {
                try {
                    Storage::disk('ftp')->delete($newMainFile);
                } catch (\Throwable) {
                }
            }

            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        $message = match (true) {
            !$this->isStaff => 'مطلب شما ثبت شد و پس از بررسی و تأیید منتشر می‌شود.',
            $this->post !== null => 'مطلب با موفقیت ویرایش شد.',
            default => 'مطلب جدید با موفقیت ثبت شد.',
        };

        session()->flash('success', $message);

        return $this->redirectRoute('posts.index');
    }

    // ---------- بررسی مطلب (تأیید / رد) ----------

    public function approve()
    {
        if (!$this->canReview) {
            return null;
        }

        $this->post->approve(auth()->user());

        session()->flash('success', 'مطلب تأیید و منتشر شد.');

        return $this->redirectRoute('posts.index');
    }

    public function reject(string $reason = '')
    {
        if (!$this->canReview || !$this->post->canBeRejected()) {
            return null;
        }

        if (trim($reason) === '') {
            $this->dispatch('notify', type: 'error', message: 'برای رد مطلب، دلیل آن را بنویسید.');
            return null;
        }

        $this->post->reject(auth()->user(), $reason);

        session()->flash('success', 'مطلب رد شد و دلیل آن برای نویسنده نمایش داده می‌شود.');

        return $this->redirectRoute('posts.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            @if ($post)
                ویرایش {{ $this->typeInfo['label'] }}: {{ \Illuminate\Support\Str::limit($post->title, 50) }}
            @elseif ($type)
                ثبت مطلب جدید — {{ $this->typeInfo['label'] }}
            @else
                ثبت مطلب جدید
            @endif
        </h5>

        <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    @if (! $type)
        {{-- مرحله اول: انتخاب دسته‌بندی --}}
        @if (empty($this->allowedTypes))
            <div class="alert alert-warning">
                هیچ دسته‌بندی‌ای برای ثبت مطلب در اختیار شما نیست. دسته‌بندی‌ها بر اساس تخصص هنری شما نمایش داده
                می‌شوند.
            </div>
        @else
            <p class="text-muted">دسته‌بندی مطلب را انتخاب کنید:</p>
            <div class="row g-3">
                @foreach ($this->allowedTypes as $key)
                    <div class="col-sm-6 col-lg-4 col-xl">
                        <a href="{{ route('posts.create', ['type' => $key]) }}"
                           class="card type-card h-100 text-center text-body">
                            <div class="card-body py-4">
                                <span class="type-icon mb-3" wire:ignore><i
                                        data-feather="{{ \App\Models\Post::TYPES[$key]['icon'] }}"></i></span>
                                <h6 class="mb-0">{{ \App\Models\Post::TYPES[$key]['label'] }}</h6>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- پیغام‌های وضعیت --}}
        @if ($post?->status === 'rejected')
            <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong>این مطلب رد شده است.</strong>
                    <div class="mt-1">دلیل: {{ $post->rejection_reason ?: 'دلیلی ثبت نشده است.' }}</div>
                    @unless ($this->isStaff)
                        <div class="mt-1 small">پس از اصلاح و ذخیره، مطلب دوباره برای بررسی ارسال می‌شود.</div>
                    @endunless
                </div>
                @if ($this->canReview)
                    <button type="button" class="btn btn-success btn-sm" wire:click="approve">تأیید و انتشار</button>
                @endif
            </div>
        @elseif ($post?->status === 'pending')
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>این مطلب در انتظار بررسی است.</span>
                @if ($this->canReview)
                    <span class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm"
                                wire:click="approve">تأیید و انتشار</button>
                        <button type="button" class="btn btn-danger btn-sm"
                                x-on:click="askReason('رد مطلب', (reason) => $wire.reject(reason), true, 'دلیل رد (به نویسنده مطلب نمایش داده می‌شود)')">رد مطلب</button>
                    </span>
                @endif
            </div>
        @endif

        <form wire:submit="save" novalidate>
            <div class="row">
                {{-- ستون اصلی --}}
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">محتوای مطلب</h6>

                            <div class="mb-3">
                                <label for="title" class="form-label">عنوان <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="type"></i></span>
                                    <input type="text" id="title" wire:model.live.debounce.400ms="title"
                                           class="form-control @error('title') is-invalid @enderror"
                                           placeholder="عنوان">
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="slug" class="form-label">slug (نامک) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="link"></i></span>
                                    <input type="text" id="slug" wire:model.blur="slug" autocomplete="off" dir="auto"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           placeholder="به صورت خودکار از عنوان ساخته می‌شود">
                                    <button type="button" wire:click="generateSlug" class="btn btn-outline-secondary"
                                            title="ساخت از روی عنوان">
                                        <span wire:ignore><i data-feather="refresh-cw"></i></span>
                                    </button>
                                    @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">فاصله‌ها به _ تبدیل می‌شوند و فقط حروف، عدد و _ مجاز است.</div>
                            </div>


                            @if ($this->typeInfo['creator'])
                                <div class="mb-3">
                                    <label for="creator_name" class="form-label">{{ $this->typeInfo['creator'] }} <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text" wire:ignore><i data-feather="user"></i></span>
                                        <input type="text" id="creator_name" wire:model="creator_name"
                                               class="form-control @error('creator_name') is-invalid @enderror"
                                               placeholder="{{ $this->typeInfo['creator'] }}">
                                        @error('creator_name')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="mb-1">
                                <label class="form-label">
                                    {{ $this->typeInfo['content_label'] }}
                                    @if ($this->typeInfo['content_required'])
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <div wire:ignore class="editor-lg">
                                    <div id="content-editor"></div>
                                </div>
                                @error('content')
                                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            @if ($this->typeInfo['media'])
                                <div class="mt-3">
                                    <label for="file_link" class="form-label">لینک فایل برای دانلود در سایت </label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text" wire:ignore><i data-feather="link"></i></span>
                                        <input type="text" id="file_link" wire:model.live.debounce.400ms="file_link"
                                               class="form-control @error('file_link') is-invalid @enderror"
                                               placeholder="لینک فایل برای دانلود در سایت">
                                        @error('file_link')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                @if( $this->typeInfo['media'] === 'video')
                                    <div class="mt-3">
                                        <label for="file_link" class="form-label">لینک آپارات</label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text" wire:ignore><i
                                                    data-feather="video"></i></span>
                                            <input type="text" id="file_link"
                                                      wire:model.live.debounce.400ms="aparat_hash"
                                                      class="form-control @error('aparat_hash') is-invalid @enderror"
                                                      placeholder="لینک آپارات">
                                            @error('aparat_hash')
                                            <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                @endif

                            @endif


                        </div>
                    </div>

                    {{-- فایل (لوح، شنیداری، تماشایی) --}}
                    @if ($this->typeInfo['media'])
                        <div class="card mb-3">
                            <div class="card-body">

                                <h6 class="card-title mb-3">{{ $this->typeInfo['media_label'] }} <span
                                        class="text-danger">*</span></h6>

                                @php($media = $this->typeInfo['media'])
                                @if ($mediaFile && ! $errors->has('mediaFile'))
                                    @if ($mediaFile->isPreviewable())
                                        @if ($media === 'image')
                                            <img src="{{ $mediaFile->temporaryUrl() }}" class="media-preview mb-2"
                                                 alt="">
                                        @elseif ($media === 'audio')
                                            <audio src="{{ $mediaFile->temporaryUrl() }}" controls
                                                   class="w-100 mb-2"></audio>
                                        @else
                                            <video src="{{ $mediaFile->temporaryUrl() }}" controls
                                                   class="media-preview mb-2"></video>
                                        @endif
                                    @endif
                                    <div class="text-success small mb-2">
                                        فایل جدید: <span dir="ltr">{{ $mediaFile->getClientOriginalName() }}</span>
                                        ({{ round($mediaFile->getSize() / 1048576, 1) }} MB)
                                    </div>
                                @elseif ($post?->media_url)
                                    @if ($media === 'image')
                                        <img src="{{ $post->media_url }}" class="media-preview mb-2" alt="">
                                    @elseif ($media === 'audio')
                                        <audio src="{{ $post->media_url }}" controls class="w-100 mb-2"></audio>
                                    @else
                                        <video src="{{ $post->media_url }}" controls class="media-preview mb-2"></video>
                                    @endif
                                @endif

                                <div x-data="{ uploading: false, progress: 0 }"
                                     x-on:livewire-upload-start="uploading = true; progress = 0"
                                     x-on:livewire-upload-progress="progress = $event.detail.progress"
                                     x-on:livewire-upload-finish="uploading = false"
                                     x-on:livewire-upload-cancel="uploading = false"
                                     x-on:livewire-upload-error="uploading = false">

                                    <label for="mediaFile" class="media-dropzone" x-show="! uploading">
                                        <span class="text-center">
                                            <span wire:ignore><i
                                                    data-feather="{{ $media === 'image' ? 'image' : ($media === 'audio' ? 'music' : 'film') }}"
                                                    class="mb-2"></i></span><br>
                                            {{ $post?->media_file || $mediaFile ? 'برای جایگزینی فایل کلیک کنید' : 'برای انتخاب فایل کلیک کنید' }}<br>
                                            <small class="text-muted">
                                                @if ($media === 'image')
                                                    jpg، png یا webp — حداکثر ۱۰ مگابایت
                                                @elseif ($media === 'audio')
                                                    mp3، wav، ogg یا m4a — حداکثر ۵۰ مگابایت
                                                @else
                                                    mp4، webm یا mov — حداکثر ۲۰۰ مگابایت
                                                @endif
                                            </small>
                                        </span>
                                    </label>
                                    <input type="file" id="mediaFile" wire:model="mediaFile" class="d-none"
                                           accept="{{ $media === 'image' ? 'image/png,image/jpeg,image/webp' : ($media === 'audio' ? 'audio/*' : 'video/mp4,video/webm,video/quicktime') }}">

                                    <div x-show="uploading" x-cloak>
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span>در حال آپلود...</span>
                                            <span x-text="progress + '%'"></span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                 x-bind:style="'width: ' + progress + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                @error('mediaFile')
                                <div class="text-danger small mt-2">{{ $message }}</div> @enderror

                            </div>
                        </div>

                        {{-- فایل با حجم اصلی (آپلود روی FTP؛ برای دکمه دانلود در سایت) --}}
                        {{--                        <div class="card mb-3">--}}
                        {{--                            <div class="card-body">--}}
                        {{--                                <h6 class="card-title mb-3">فایل با حجم اصلی (برای دانلود در سایت)</h6>--}}

                        {{--                                @if (! $this->ftpEnabled)--}}
                        {{--                                    <div class="alert alert-warning small mb-0">اتصال FTP تنظیم نشده است.</div>--}}
                        {{--                                @else--}}
                        {{--                                    @if ($mainFile && ! $errors->has('mainFile'))--}}
                        {{--                                        <div class="text-success small mb-2">--}}
                        {{--                                            فایل جدید: <span dir="ltr">{{ $mainFile->getClientOriginalName() }}</span>--}}
                        {{--                                            ({{ round($mainFile->getSize() / 1048576, 1) }} MB)--}}
                        {{--                                        </div>--}}
                        {{--                                    @elseif ($post?->main_file)--}}
                        {{--                                        <div class="small mb-2">--}}
                        {{--                                            فایل فعلی: <span dir="ltr">{{ $post->main_file_name }}</span>--}}
                        {{--                                            @if ($post->main_file_size_label) ({{ $post->main_file_size_label }}) @endif--}}
                        {{--                                            — <a href="{{ $post->main_file_url }}" target="_blank" rel="noopener">دانلود</a>--}}
                        {{--                                        </div>--}}
                        {{--                                    @endif--}}




                        {{--                                    <div x-data="{ uploading: false, progress: 0 }"--}}
                        {{--                                         x-on:livewire-upload-start="uploading = true; progress = 0"--}}
                        {{--                                         x-on:livewire-upload-progress="progress = $event.detail.progress"--}}
                        {{--                                         x-on:livewire-upload-finish="uploading = false"--}}
                        {{--                                         x-on:livewire-upload-cancel="uploading = false"--}}
                        {{--                                         x-on:livewire-upload-error="uploading = false">--}}

                        {{--                                        <label for="mainFile" class="media-dropzone" x-show="! uploading">--}}
                        {{--                                            <span class="text-center">--}}
                        {{--                                                <span wire:ignore><i data-feather="download-cloud" class="mb-2"></i></span><br>--}}
                        {{--                                                {{ $post?->main_file || $mainFile ? 'برای جایگزینی فایل کلیک کنید' : 'برای انتخاب فایل کلیک کنید' }}<br>--}}
                        {{--                                                <small class="text-muted">--}}
                        {{--                                                    @if ($media === 'image') jpg، png، webp یا tiff--}}
                        {{--                                                    @elseif ($media === 'audio') mp3، wav، flac، ogg، m4a یا aac--}}
                        {{--                                                    @else mp4، webm، mov یا mkv--}}
                        {{--                                                    @endif--}}
                        {{--                                                    — حداکثر 200 مگابایت--}}
                        {{--                                                </small>--}}
                        {{--                                            </span>--}}
                        {{--                                        </label>--}}
                        {{--                                        <input type="file" id="mainFile" wire:model="mainFile" class="d-none"--}}
                        {{--                                               accept="{{ $media === 'image' ? 'image/*,.tif,.tiff' : ($media === 'audio' ? 'audio/*,.flac' : 'video/*,.mkv') }}">--}}

                        {{--                                        <div x-show="uploading" x-cloak>--}}
                        {{--                                            <div class="d-flex justify-content-between small mb-1">--}}
                        {{--                                                <span>در حال آپلود...</span>--}}
                        {{--                                                <span x-text="progress + '%'"></span>--}}
                        {{--                                            </div>--}}
                        {{--                                            <div class="progress">--}}
                        {{--                                                <div class="progress-bar progress-bar-striped progress-bar-animated" x-bind:style="'width: ' + progress + '%'"></div>--}}
                        {{--                                            </div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <p class="text-muted small mt-2 mb-0">اختیاری - اگر این بخش انتخاب نشود لینک فایل انتخاب شده در بالا به عنوان لینک دانلود به کاربر نمایش داده می شود .</p>--}}
                        {{--                                    @error('mainFile') <div class="text-danger small mt-2">{{ $message }}</div> @enderror--}}
                        {{--                                @endif--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}
                    @endif
                </div>

                {{-- ستون کناری --}}
                <div class="col-lg-4">
                    {{-- انتشار --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">انتشار</h6>

                            @if ($this->isStaff)

                                @can('choose-author')
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">ثبت‌کننده مطلب <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text" wire:ignore><i
                                                    data-feather="user"></i></span>
                                            <select id="user_id" wire:model="user_id"
                                                    class="form-select @error('user_id') is-invalid @enderror">
                                                <option value="">انتخاب کاربر...</option>
                                                @foreach ($this->userOptions as $u)
                                                    <option
                                                        value="{{ $u->id }}" @selected((string) $user_id === (string) $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('user_id')
                                            <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        @if ($post?->mainUser)
                                            <div class="form-text">ثبت‌شده توسط: {{ $post->mainUser->name }}</div>
                                        @endif
                                    </div>
                                @endcan

                                <div class="mb-3">
                                    <label class="form-label d-block">وضعیت انتشار</label>
                                    <div class="btn-group w-100" role="group">
                                        @foreach (['draft' => 'پیش‌نویس', 'published' => 'منتشر شده'] as $value => $label)
                                            <input type="radio" class="btn-check" wire:model.live="status"
                                                   id="status-{{ $value }}" value="{{ $value }}">
                                            <label
                                                class="btn btn-outline-{{ $value === 'published' ? 'success' : 'warning' }}"
                                                for="status-{{ $value }}">{{ $label }}</label>
                                        @endforeach
                                    </div>
                                    @if (in_array($status, ['pending', 'rejected']))
                                        <div class="form-text">وضعیت فعلی: {{ \App\Models\Post::STATUSES[$status] }} —
                                            با انتخاب یکی از گزینه‌ها تغییر می‌کند.
                                        </div>
                                    @endif
                                    @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="published_at_picker" class="form-label">تاریخ و ساعت انتشار</label>
                                    <div class="input-group" wire:ignore>
                                        <span class="input-group-text"><i data-feather="clock"></i></span>
                                        <input type="text" id="published_at_picker" class="form-control bg-body"
                                               placeholder="انتخاب از تقویم" readonly autocomplete="off">
                                        <button type="button" id="published_at_clear" class="btn btn-outline-secondary"
                                                title="پاک کردن تاریخ">
                                            <i data-feather="x"></i>
                                        </button>
                                    </div>
                                    @error('published_at')
                                    <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    <div class="form-text">
                                        @if ($status === 'published')
                                            اگر خالی بماند، همین الان منتشر می‌شود. اگر زمان آینده انتخاب شود، مطلب
                                            زمان‌بندی می‌شود.
                                        @else
                                            مطلبی که منتشر نشده در سایت نمایش داده نمی‌شود.
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info small">
                                    پس از ثبت، مطلب شما برای بررسی ارسال می‌شود و بعد از تأیید منتشر خواهد شد.
                                    @if ($post?->status === 'published')
                                        <br>با ویرایش این مطلب، تا تأیید دوباره از سایت برداشته می‌شود.
                                    @endif
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary btn-icon-text w-100"
                                    wire:loading.attr="disabled" wire:target="save, image, mediaFile, mainFile">
                                <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                                <span wire:loading.remove wire:target="save">
                                    @if (! $this->isStaff)
                                        ارسال برای بررسی
                                    @elseif ($post)
                                        ذخیره تغییرات
                                    @else
                                        ثبت مطلب
                                    @endif
                                </span>
                                <span wire:loading wire:target="save">در حال ذخیره...</span>
                            </button>
                        </div>
                    </div>

                    {{-- پوستر --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">{{ $this->typeInfo['poster_label'] }}</h6>

                            <label for="image" class="post-image-upload mb-2">
                                @php($preview = $image && ! $errors->has('image') ? $image->temporaryUrl() : $post?->image_url)
                                @if ($preview)
                                    <img src="{{ $preview }}" alt="پوستر">
                                @else
                                    <span class="text-muted text-center">
                                        <span wire:ignore><i data-feather="image" class="mb-2"></i></span><br>
                                        برای انتخاب تصویر کلیک کنید
                                    </span>
                                @endif

                                <div class="post-image-loading" wire:loading.flex wire:target="image">
                                    <div class="spinner-border text-light" role="status"></div>
                                </div>
                            </label>
                            <input type="file" id="image" wire:model="image" accept="image/png,image/jpeg,image/webp"
                                   class="d-none">

                            <p class="text-muted small mb-0">اختیاری — jpg، png، webp تا ۴ مگابایت</p>
                            @error('image')
                            <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- دسته‌بندی (فقط اخبار) و برچسب --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">{{ $type === 'news' ? 'دسته‌بندی و برچسب' : 'برچسب‌ها' }}</h6>

                            @if ($type === 'news')
                                <div class="mb-3">
                                    <label for="categories" class="form-label">دسته‌بندی‌ها</label>
                                    <div wire:ignore>
                                        <select id="categories" class="form-select" multiple>
                                            @foreach ($this->categoryOptions as $option)
                                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('categories')
                                    <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    @error('categories.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <div>
                                @if ($type === 'news')
                                    <label for="tags" class="form-label">برچسب‌ها</label>
                                @endif
                                <div wire:ignore>
                                    <select id="tags" class="form-select" multiple>
                                        @foreach ($this->allTags as $tag)
                                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('tags')
                                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @error('tags.*')
                                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

@assets
<script src="{{ asset('assets/admin/vendors/ckeditor5/ckeditor5.umd.js') }}"></script>
<script src="{{ asset('assets/admin/vendors/ckeditor5/translations/fa.umd.js') }}"></script>
@endassets

@script
<script>
    // در مرحله انتخاب دسته‌بندی، فرم هنوز نمایش داده نشده است
    if (document.querySelector('#content-editor')) {
        // ویرایشگر متن (CKEditor 5 با امکان راست‌چین، وسط‌چین و چپ‌چین)
        createEditor(document.querySelector('#content-editor'), $wire, 'content');

        // انتخاب چندگانه دسته‌بندی و برچسب
        const select2Options = (placeholder) => ({
            dir: 'rtl',
            width: '100%',
            placeholder: placeholder,
            closeOnSelect: false,
            language: {noResults: () => 'موردی یافت نشد'},
        });

        const tagSelect = $('#tags');
        tagSelect.select2(select2Options('برچسب‌ها را انتخاب کنید'));
        tagSelect.val($wire.tags).trigger('change');
        tagSelect.on('change', () => $wire.tags = tagSelect.val() ?? []);

        const categorySelect = $('#categories');
        if (categorySelect.length) {
            categorySelect.select2(select2Options('دسته‌بندی‌ها را انتخاب کنید'));
            categorySelect.val($wire.categories).trigger('change');
            categorySelect.on('change', () => $wire.categories = categorySelect.val() ?? []);
        }
    }

    // تقویم شمسی همراه با ساعت (فقط برای مدیر و نویسنده)
    const publishInput = $('#published_at_picker');

    if (publishInput.length) {
        const publishPicker = publishInput.persianDatepicker({
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
                const unix = publishPicker.getState().selected.unixDate;
                $wire.published_at = Math.floor(unix / 60000) * 60; // ثانیه‌ها حذف می‌شوند
            }),
        });

        if ($wire.published_at) {
            publishPicker.setDate($wire.published_at * 1000);
        }

        $('#published_at_clear').on('click', () => {
            publishInput.val('');
            $wire.published_at = null;
        });
    }
</script>
@endscript
