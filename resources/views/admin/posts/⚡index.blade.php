<?php

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('مطالب')] class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = '';

    #[Url(history: true)]
    public string $type = '';

    #[Url(as: 'category', history: true)]
    public string $categoryId = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'type', 'categoryId'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'type', 'categoryId');
        $this->resetPage();
    }

    // هنرمندان (بدون دسترسی دیدن همه مطالب) فقط مطالب خودشان را می‌بینند
    #[Computed]
    public function onlyOwn(): bool
    {
        return ! Gate::any(['view-content', 'review-content']);
    }

    private function baseQuery()
    {
        return Post::query()
            ->when($this->onlyOwn, fn ($q) => $q->where('user_id', auth()->id()))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type));
    }

    // تعداد مطالب در هر وضعیت (برای تب‌ها)
    #[Computed]
    public function counts(): array
    {
        $counts = $this->baseQuery()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return ['' => $counts->sum()] + collect(Post::STATUSES)->map(fn ($label, $key) => $counts[$key] ?? 0)->all();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function posts()
    {
        $term = trim($this->search);

        return $this->baseQuery()
            ->with(['user:id,first_name,last_name', 'categories:id,name'])
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('creator_name', 'like', $like));
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->categoryId !== '', fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $this->categoryId)))
            ->latest('id')
            ->paginate(10);
    }

    // ---------- بررسی مطالب ----------

    // مطلب در انتظار بررسی یا رد شده تأیید و منتشر می‌شود
    public function approvePost(int $id): void
    {
        $post = Post::find($id);

        if (! $post || ! $post->canBeApproved() || Gate::denies('review-content')) {
            return;
        }

        $post->approve(auth()->user());

        $this->dispatch('notify', type: 'success', message: 'مطلب تأیید و منتشر شد.');
    }

    public function rejectPost(int $id, string $reason = ''): void
    {
        $post = Post::find($id);

        if (! $post || ! $post->canBeRejected() || Gate::denies('review-content')) {
            return;
        }

        if (trim($reason) === '') {
            $this->dispatch('notify', type: 'error', message: 'برای رد مطلب، دلیل آن را بنویسید.');
            return;
        }

        $post->reject(auth()->user(), $reason);

        $this->dispatch('notify', type: 'success', message: 'مطلب رد شد.');
    }

    // ---------- نمایش محتوا در مودال ----------

    public ?int $viewingId = null;

    public function showPost(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closePost(): void
    {
        $this->viewingId = null;
    }

    #[Computed]
    public function viewingPost(): ?Post
    {
        if (! $this->viewingId) {
            return null;
        }

        $post = Post::with(['user', 'reviewer', 'categories:id,name', 'tags:id,name'])->find($this->viewingId);

        // هنرمند فقط مطالب خودش را می‌بیند
        if ($post && $this->onlyOwn && $post->user_id !== auth()->id()) {
            return null;
        }

        return $post;
    }

    public function deletePost(int $id): void
    {
        $post = Post::find($id);

        if (! $post) {
            $this->dispatch('notify', type: 'error', message: 'مطلب مورد نظر پیدا نشد.');
            return;
        }

        if (Gate::denies('delete-post', $post)) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی حذف این مطلب را ندارید.');
            return;
        }

        $post->delete();

        $this->dispatch('notify', type: 'success', message: 'مطلب با موفقیت حذف شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $this->onlyOwn ? 'مطالب من' : 'لیست مطالب' }}</h5>
        @can('create-post')
            <a href="{{ route('posts.create') }}" class="btn btn-primary">ثبت مطلب جدید</a>
        @endcan
    </div>

    {{-- دسته‌بندی‌های اصلی --}}
    <ul class="nav nav-pills mb-2 gap-2">
        @foreach (['' => 'همه دسته‌بندی‌ها'] + collect(\App\Models\Post::TYPES)->map(fn ($t) => $t['label'])->all() as $key => $label)
            <li class="nav-item">
                <button type="button" wire:click="$set('type', '{{ $key }}')" @class(['nav-link py-1', 'active' => $type === $key])>{{ $label }}</button>
            </li>
        @endforeach
    </ul>

    {{-- وضعیت --}}
    <ul class="nav nav-tabs mb-3">
        @foreach (['' => 'همه'] + \App\Models\Post::STATUSES as $key => $label)
            <li class="nav-item">
                <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['nav-link', 'active' => $status === $key])>
                    {{ $label }}
                    <span @class(['badge ms-1', 'bg-danger' => $key === 'pending' && $this->counts[$key] > 0, 'bg-secondary' => ! ($key === 'pending' && $this->counts[$key] > 0)])>{{ $this->counts[$key] }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div @class(['col-md-5' => $type === 'news', 'col-md-10' => $type !== 'news'])>
                    <label for="search" class="form-label">جستجو</label>
                    <input type="text" id="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="عنوان، slug یا نام صاحب اثر">
                </div>

                @if ($type === 'news')
                    <div class="col-md-5">
                        <label for="categoryId" class="form-label">دسته‌بندی خبر</label>
                        <select id="categoryId" wire:model.live="categoryId" class="form-select">
                            <option value="">همه</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary w-100">پاک کردن</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive" wire:loading.class="opacity-50">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>عنوان</th>
                        <th>دسته‌بندی</th>
                        <th>نویسنده / صاحب اثر</th>
                        <th>وضعیت</th>
                        <th>تاریخ انتشار</th>
                        <th>بازدید</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->posts as $post)
                        <tr wire:key="post-{{ $post->id }}" wire:click="showPost({{ $post->id }})" class="post-row" title="نمایش محتوا">
                            <td>{{ $this->posts->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if ($post->image_url)
                                        <img src="{{ $post->image_url }}" class="rounded me-2" width="48" height="36" style="object-fit: cover" alt="">
                                    @endif
                                    <div>
                                        <div>{{ \Illuminate\Support\Str::limit($post->title, 50) }}</div>
                                        <small class="text-muted" dir="auto">{{ \Illuminate\Support\Str::limit($post->slug, 40) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $post->typeConfig()['label'] }}</span>
                                @foreach ($post->categories as $category)
                                    <span class="badge bg-secondary">{{ $category->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <div>{{ $post->user?->first_name }} {{ $post->user?->last_name }}</div>
                                @if ($post->creator_name)
                                    <small class="text-muted">{{ $post->typeConfig()['creator'] }}: {{ $post->creator_name }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($post->status === 'draft')
                                    <span class="badge bg-warning text-dark">پیش‌نویس</span>
                                @elseif ($post->status === 'pending')
                                    <span class="badge bg-info text-dark">در انتظار بررسی</span>
                                @elseif ($post->status === 'rejected')
                                    <span class="badge bg-danger">رد شده</span>
                                    <div class="rejection-reason small mt-1">
                                        {{ $post->rejection_reason ? 'دلیل: '.$post->rejection_reason : 'دلیلی ثبت نشده است.' }}
                                    </div>
                                @elseif ($post->published_at?->isFuture())
                                    <span class="badge bg-info text-dark">زمان‌بندی شده</span>
                                @else
                                    <span class="badge bg-success">منتشر شده</span>
                                @endif
                            </td>
                            <td dir="ltr" class="text-end">{{ $post->published_at_jalali ?? '—' }}</td>
                            <td>{{ number_format($post->views_count) }}</td>
                            {{-- کلیک روی دکمه‌های عملیات، مودال را باز نکند --}}
                            <td class="text-nowrap" x-on:click.stop>
                                @can('review-content')
                                    @if ($post->canBeApproved())
                                        <a href="#" title="تأیید و انتشار" wire:click.prevent="approvePost({{ $post->id }})">
                                            <i class="text-success list-icon" data-feather="check-circle"></i>
                                        </a>
                                    @endif
                                    @if ($post->canBeRejected())
                                        <a href="#" title="رد مطلب"
                                           x-on:click.prevent="askReason(@js('رد مطلب «'.\Illuminate\Support\Str::limit($post->title, 40).'»'), (reason) => $wire.rejectPost({{ $post->id }}, reason), true, 'دلیل رد (به نویسنده مطلب نمایش داده می‌شود)')">
                                            <i class="text-warning list-icon" data-feather="x-circle"></i>
                                        </a>
                                    @endif
                                @endcan

                                @can('update-post', $post)
                                    <a href="{{ route('posts.edit', ['post' => $post]) }}" title="ویرایش">
                                        <i class="list-icon" data-feather="edit"></i>
                                    </a>
                                @endcan

                                @can('delete-post', $post)
                                    <a href="#" title="حذف"
                                       x-on:click.prevent="confirmDelete(() => $wire.deletePost({{ $post->id }}), @js('مطلب «'.\Illuminate\Support\Str::limit($post->title, 60).'» حذف خواهد شد.'))">
                                        <i class="text-danger list-icon" data-feather="trash"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">مطلبی پیدا نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->posts->links() }}
            </div>
        </div>
    </div>

    {{-- مودال نمایش محتوا (برای بررسی راحت‌تر) --}}
    @if ($p = $this->viewingPost)
        @php($info = $p->typeConfig())
        <div class="modal d-block" tabindex="-1" wire:key="post-modal-{{ $p->id }}"
             x-data x-on:keydown.escape.window="$wire.closePost()" x-on:click.self="$wire.closePost()">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1">{{ $p->title }}</h5>
                            <span class="badge bg-primary">{{ $info['label'] }}</span>
                            @if ($p->status === 'draft')
                                <span class="badge bg-warning text-dark">پیش‌نویس</span>
                            @elseif ($p->status === 'pending')
                                <span class="badge bg-info text-dark">در انتظار بررسی</span>
                            @elseif ($p->status === 'rejected')
                                <span class="badge bg-danger">رد شده</span>
                            @else
                                <span class="badge bg-success">منتشر شده</span>
                            @endif
                            @foreach ($p->categories as $category)
                                <span class="badge bg-secondary">{{ $category->name }}</span>
                            @endforeach
                        </div>
                        <button type="button" class="btn-close ms-0" wire:click="closePost" aria-label="بستن"></button>
                    </div>

                    <div class="modal-body">
                        @if ($p->status === 'rejected')
                            <div class="alert alert-danger py-2">
                                <strong>دلیل رد:</strong> {{ $p->rejection_reason ?: 'دلیلی ثبت نشده است.' }}
                                @if ($p->reviewer)
                                    <div class="small mt-1">رد شده توسط {{ $p->reviewer->first_name }} {{ $p->reviewer->last_name }} — <bdi>{{ jdate($p->reviewed_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d H:i') }}</bdi></div>
                                @endif
                            </div>
                        @endif

                        <div class="row g-4">
                            {{-- محتوا --}}
                            <div class="col-lg-8">
                                @if ($p->media_url)
                                    <div class="mb-3">
                                        <div class="text-muted small mb-1">{{ $info['media_label'] ?? 'فایل اصلی' }}</div>
                                        @if ($info['media'] === 'image')
                                            <a href="{{ $p->media_url }}" target="_blank"><img src="{{ $p->media_url }}" class="preview-media" alt=""></a>
                                        @elseif ($info['media'] === 'audio')
                                            <audio src="{{ $p->media_url }}" controls class="w-100"></audio>
                                        @elseif ($info['media'] === 'video')
                                            <video src="{{ $p->media_url }}" controls class="preview-media"></video>
                                        @endif
                                    </div>
                                @endif

                                <div class="text-muted small mb-1">{{ $info['content_label'] }}</div>
                                <div class="preview-content">
                                    @if ($p->content)
                                        {!! \App\Support\HtmlSanitizer::clean($p->content) !!}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>

                            {{-- مشخصات --}}
                            <div class="col-lg-4">
                                @if ($p->image_url)
                                    <img src="{{ $p->image_url }}" class="preview-poster mb-3" alt="">
                                @endif

                                <ul class="list-unstyled preview-meta mb-0">
                                    @if ($info['creator'])
                                        <li><span>{{ $info['creator'] }}</span>{{ $p->creator_name ?: '—' }}</li>
                                    @endif
                                    <li><span>ثبت کننده</span>{{ $p->user?->first_name }} {{ $p->user?->last_name }}</li>
                                    <li><span>تاریخ ثبت</span><bdi>{{ jdate($p->created_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d H:i') }}</bdi></li>
                                    <li><span>تاریخ انتشار</span><bdi>{{ $p->published_at_jalali ?? '—' }}</bdi></li>
                                    @if ($p->reviewer)
                                        <li><span>بررسی شده توسط</span>{{ $p->reviewer->first_name }} {{ $p->reviewer->last_name }} — <bdi>{{ jdate($p->reviewed_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d H:i') }}</bdi></li>
                                    @endif
                                    <li><span>slug</span><bdi>{{ $p->slug }}</bdi></li>
                                    @if ($p->tags->isNotEmpty())
                                        <li><span>برچسب‌ها</span>
                                            @foreach ($p->tags as $tag)
                                                <span class="badge bg-light text-dark border">#{{ $tag->name }}</span>
                                            @endforeach
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        @can('review-content')
                            @if ($p->canBeApproved())
                                <button type="button" class="btn btn-success" wire:click="approvePost({{ $p->id }})">تأیید و انتشار</button>
                            @endif
                            @if ($p->canBeRejected())
                                <button type="button" class="btn btn-danger"
                                        x-on:click="askReason(@js('رد مطلب «'.\Illuminate\Support\Str::limit($p->title, 40).'»'), (reason) => $wire.rejectPost({{ $p->id }}, reason), true, 'دلیل رد (به نویسنده مطلب نمایش داده می‌شود)')">رد مطلب</button>
                            @endif
                        @endcan
                        @can('update-post', $p)
                            <a href="{{ route('posts.edit', ['post' => $p]) }}" class="btn btn-primary">ویرایش</a>
                        @endcan
                        <button type="button" class="btn btn-secondary" wire:click="closePost">بستن</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop show"></div>
    @endif
</div>

