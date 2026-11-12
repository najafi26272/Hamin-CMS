<?php

use App\Models\Video;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('فیلم‌ها')] class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    #[Computed]
    public function videos()
    {
        $term = trim($this->search);

        return Video::query()
            ->with('user:id,first_name,last_name')
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like));
            })
            ->latest('id')
            ->paginate(10);
    }

    public function deleteVideo(int $id): void
    {
        $video = Video::find($id);

        if (! $video) {
            $this->dispatch('notify', type: 'error', message: 'فیلم مورد نظر پیدا نشد.');
            return;
        }

        $video->delete();

        $this->dispatch('notify', type: 'success', message: 'فیلم با موفقیت حذف شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">فیلم‌ها</h5>
        <a href="{{ route('videos.create') }}" class="btn btn-primary">ثبت فیلم جدید</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="search" class="form-label">جستجو</label>
                    <input type="text"
                           id="search"
                           wire:model.live.debounce.400ms="search"
                           class="form-control"
                           placeholder="عنوان یا slug فیلم">
                </div>

                <div class="col-md-4">
                    <button type="button"
                            wire:click="resetFilters"
                            class="btn btn-outline-secondary">
                        پاک کردن جستجو
                    </button>
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
                        <th>حجم فایل</th>
                        <th>ثبت کننده</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->videos as $video)
                        <tr wire:key="video-{{ $video->id }}">
                            <td>{{ $this->videos->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="video-thumb me-2">
                                        @if ($video->thumbnail_url)
                                            <img src="{{ $video->thumbnail_url }}" alt="">
                                        @endif
                                        <span wire:ignore><i data-feather="play-circle"></i></span>
                                    </div>
                                    <div>
                                        <div>{{ \Illuminate\Support\Str::limit($video->title, 50) }}</div>
                                        <small class="text-muted" dir="auto">{{ \Illuminate\Support\Str::limit($video->slug, 40) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $video->size_mb !== null ? $video->size_mb.' MB' : '—' }}</td>
                            <td>{{ $video->user?->first_name }} {{ $video->user?->last_name }}</td>
                            <td dir="ltr" class="text-end">{{ jdate($video->created_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d') }}</td>
                            <td>
                                <a href="{{ $video->video_url }}" target="_blank" title="پخش">
                                    <i class="list-icon" data-feather="play"></i>
                                </a>

                                <a href="{{ route('videos.edit', ['video' => $video]) }}" title="ویرایش">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="#" title="حذف"
                                   x-on:click.prevent="confirmDelete(() => $wire.deleteVideo({{ $video->id }}), @js('فیلم «'.\Illuminate\Support\Str::limit($video->title, 60).'» حذف خواهد شد.'))">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                فیلمی پیدا نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->videos->links() }}
            </div>
        </div>
    </div>
</div>

@assets
<style>
    .video-thumb {
        position: relative;
        width: 64px;
        height: 40px;
        border-radius: 6px;
        overflow: hidden;
        background: var(--bs-secondary-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .video-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .video-thumb svg {
        position: relative;
        width: 20px;
        height: 20px;
        color: #fff;
        filter: drop-shadow(0 0 2px rgba(0, 0, 0, .6));
    }
</style>
@endassets
