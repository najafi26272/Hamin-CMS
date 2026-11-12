<?php

use App\Models\Comment;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    #[Computed]
    public function stats(): array
    {
        return [
            'posts' => Post::count(),
            'published' => Post::where('status', 'published')->count(),
            'draft' => Post::where('status', 'draft')->count(),
            'users' => User::count(),
            'pendingComments' => Comment::where('status', 'pending')->count(),
            'pendingUsers' => User::where('artist_status', 'pending')->count(),
            'pendingPosts' => Post::where('status', 'pending')->count(),
            // مطالب خود کاربر (برای هنرمندان)
            'myPosts' => Post::where('user_id', auth()->id())->count(),
            'myPublished' => Post::where('user_id', auth()->id())->where('status', 'published')->count(),
            'myRejected' => Post::where('user_id', auth()->id())->where('status', 'rejected')->count(),
        ];
    }

    // آمار بازدید امروز، این هفته، این ماه و امسال (تقویم شمسی)
    #[Computed]
    public function visits(): array
    {
        $labels = ['today' => 'امروز', 'week' => 'این هفته', 'month' => 'این ماه', 'year' => 'امسال'];

        return collect(PageView::periodStarts())
            ->map(fn ($from, $key) => ['label' => $labels[$key]] + PageView::statsSince($from))
            ->all();
    }

    // ۱۰ مطلب پربازدید منتشر شده
    #[Computed]
    public function topPosts()
    {
        return Post::query()
            ->with('categories:id,name')
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">داشبورد</h4>
            <p class="text-muted mb-0">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}، خوش آمدید.</p>
        </div>
    </div>

    @php
        // هر کارت فقط برای کسی نمایش داده می‌شود که به آن بخش دسترسی دارد
        $cards = [];

        if (\Illuminate\Support\Facades\Gate::allows('view-content')) {
            $cards[] = ['title' => 'کل پست‌ها', 'value' => $this->stats['posts'], 'icon' => 'file-text', 'color' => 'primary', 'url' => route('posts.index')];
            $cards[] = ['title' => 'پست‌های منتشر شده', 'value' => $this->stats['published'], 'icon' => 'check-circle', 'color' => 'success', 'url' => route('posts.index', ['status' => 'published'])];
            $cards[] = ['title' => 'پست‌های منتشر نشده', 'value' => $this->stats['draft'], 'icon' => 'edit-3', 'color' => 'warning', 'url' => route('posts.index', ['status' => 'draft'])];
        }

        if (\Illuminate\Support\Facades\Gate::allows('review-content')) {
            $cards[] = ['title' => 'مطالب در انتظار بررسی', 'value' => $this->stats['pendingPosts'], 'icon' => 'clock', 'color' => 'danger', 'url' => route('posts.index', ['status' => 'pending'])];
        }

        // هنرمندان: آمار مطالب خودشان
        if (\Illuminate\Support\Facades\Gate::allows('submit-content') && ! \Illuminate\Support\Facades\Gate::any(['view-content', 'review-content'])) {
            $cards[] = ['title' => 'مطالب من', 'value' => $this->stats['myPosts'], 'icon' => 'file-text', 'color' => 'primary', 'url' => route('posts.index')];
            $cards[] = ['title' => 'منتشر شده', 'value' => $this->stats['myPublished'], 'icon' => 'check-circle', 'color' => 'success', 'url' => route('posts.index', ['status' => 'published'])];
            $cards[] = ['title' => 'رد شده', 'value' => $this->stats['myRejected'], 'icon' => 'x-circle', 'color' => 'danger', 'url' => route('posts.index', ['status' => 'rejected'])];
        }

        if (\Illuminate\Support\Facades\Gate::allows('view-users')) {
            $cards[] = ['title' => 'کاربران', 'value' => $this->stats['users'], 'icon' => 'users', 'color' => 'info', 'url' => route('users.index')];
            $cards[] = ['title' => 'درخواست‌های هنرمندی در انتظار', 'value' => $this->stats['pendingUsers'], 'icon' => 'user-check', 'color' => 'warning', 'url' => route('users.index', ['status' => 'pending'])];
        }

        if (\Illuminate\Support\Facades\Gate::allows('manage-comments')) {
            $cards[] = ['title' => 'دیدگاه‌های در انتظار', 'value' => $this->stats['pendingComments'], 'icon' => 'message-square', 'color' => 'danger', 'url' => route('comments.index', ['status' => 'pending'])];
        }
    @endphp

    @php($artistStatus = auth()->user()->artist_status)
    @php($canApplyAsArtist = $artistStatus !== 'approved' && ! \Illuminate\Support\Facades\Gate::any(['manage-content', 'edit-content']))

    {{-- دعوت به ثبت‌نام هنرمندی / وضعیت درخواست --}}
    @if ($canApplyAsArtist)
        <div class="card mb-4 border-primary">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary" wire:ignore><i data-feather="feather"></i></div>
                <div class="flex-grow-1">
                    @if ($artistStatus === 'pending')
                        <h6 class="mb-1">درخواست هنرمندی شما در انتظار بررسی است</h6>
                        <p class="text-muted mb-0">پس از تأیید مدیر، امکان ارسال آثار برای شما فعال می‌شود.</p>
                    @elseif ($artistStatus === 'rejected')
                        <h6 class="mb-1 text-danger">درخواست هنرمندی شما تأیید نشد</h6>
                        <p class="text-muted mb-0">دلیل را ببینید، اطلاعات را اصلاح کنید و دوباره ارسال کنید.</p>
                    @else
                        <h6 class="mb-1">هنرمند هستید؟</h6>
                        <p class="text-muted mb-0">با تکمیل پروفایل هنری و تأیید مدیر، می‌توانید آثار خود را در سایت منتشر کنید.</p>
                    @endif
                </div>
                <a href="{{ route('artist.apply') }}" class="btn btn-primary">
                    {{ $artistStatus ? 'مشاهده درخواست' : 'ثبت‌نام به عنوان هنرمند' }}
                </a>
            </div>
        </div>
    @elseif (empty($cards))
        <div class="alert alert-info">هنوز دسترسی به بخشی از پنل برای شما تعریف نشده است.</div>
    @endif

    <div class="row">
        @foreach ($cards as $card)
            <div class="col-sm-6 col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-{{ $card['color'] }} bg-opacity-10 text-{{ $card['color'] }} me-3" wire:ignore>
                            <i data-feather="{{ $card['icon'] }}"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1">{{ $card['title'] }}</p>
                            <h3 class="mb-0">{{ number_format($card['value']) }}</h3>
                        </div>
                    </div>
                    @isset($card['url'])
                        <a href="{{ $card['url'] }}" class="stretched-link" aria-label="{{ $card['title'] }}"></a>
                    @endisset
                </div>
            </div>
        @endforeach
    </div>

    @can('view-content')
        {{-- آمار بازدید --}}
        <h6 class="mb-3">آمار بازدید سایت</h6>
        <div class="row">
            @foreach ($this->visits as $key => $visit)
                <div class="col-sm-6 col-xl-3 mb-4">
                    <div @class(['card h-100', 'visit-today' => $key === 'today'])>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">بازدید {{ $visit['label'] }}</span>
                                <span class="text-primary" wire:ignore><i data-feather="{{ $key === 'today' ? 'activity' : 'bar-chart-2' }}" class="icon-md"></i></span>
                            </div>
                            <h3 class="mb-1">{{ number_format($visit['views']) }}</h3>
                            <small class="text-muted">{{ number_format($visit['visitors']) }} بازدیدکننده یکتا</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- مطالب پربازدید --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title mb-0">۱۰ مطلب پربازدید</h6>
                    <a href="{{ route('posts.index') }}" class="small">همه مطالب</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th style="width: 50px">رتبه</th>
                            <th>عنوان</th>
                            <th>دسته‌بندی</th>
                            <th>تاریخ انتشار</th>
                            <th>بازدید</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php($maxViews = max(1, $this->topPosts->max('views_count')))
                        @forelse ($this->topPosts as $post)
                            <tr wire:key="top-post-{{ $post->id }}">
                                <td>
                                    <span @class(['rank', 'rank-top' => $loop->iteration <= 3])>{{ $loop->iteration }}</span>
                                </td>
                                <td>
                                    @can('edit-content')
                                        <a href="{{ route('posts.edit', ['post' => $post]) }}" class="text-body">{{ \Illuminate\Support\Str::limit($post->title, 60) }}</a>
                                    @else
                                        {{ \Illuminate\Support\Str::limit($post->title, 60) }}
                                    @endcan
                                </td>
                                <td>
                                    @foreach ($post->categories as $category)
                                        <span class="badge bg-secondary">{{ $category->name }}</span>
                                    @endforeach
                                </td>
                                <td dir="ltr" class="text-end text-muted">{{ jdate($post->published_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d') }}</td>
                                <td style="min-width: 160px">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px">
                                            <div class="progress-bar" style="width: {{ round($post->views_count / $maxViews * 100) }}%"></div>
                                        </div>
                                        <span class="fw-bold">{{ number_format($post->views_count) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">هنوز مطلب منتشرشده‌ای وجود ندارد.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endcan
</div>

@assets
<style>
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-icon svg {
        width: 26px;
        height: 26px;
    }

    .visit-today {
        border-color: #6571ff;
    }

    .rank {
        display: inline-flex;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        background: var(--bs-secondary-bg);
    }

    .rank-top {
        background: #ff6600;
        color: #fff;
    }
</style>
@endassets
