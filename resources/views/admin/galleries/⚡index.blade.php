<?php

use App\Models\Gallery;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('گالری تصاویر')] class extends Component
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
    public function galleries()
    {
        $term = trim($this->search);

        return Gallery::query()
            ->with(['user:id,first_name,last_name', 'images'])
            ->withCount('images')
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like));
            })
            ->latest('id')
            ->paginate(10);
    }

    public function deleteGallery(int $id): void
    {
        $gallery = Gallery::find($id);

        if (! $gallery) {
            $this->dispatch('notify', type: 'error', message: 'گالری مورد نظر پیدا نشد.');
            return;
        }

        $gallery->delete();

        $this->dispatch('notify', type: 'success', message: 'گالری با موفقیت حذف شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">گالری تصاویر</h5>
        <a href="{{ route('galleries.create') }}" class="btn btn-primary">ثبت گالری جدید</a>
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
                           placeholder="عنوان یا slug گالری">
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
                        <th>تصاویر</th>
                        <th>تعداد</th>
                        <th>ثبت کننده</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->galleries as $gallery)
                        <tr wire:key="gallery-{{ $gallery->id }}">
                            <td>{{ $this->galleries->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    {{-- کاور: تصویر شاخص یا اولین تصویر گالری --}}
                                    @php($cover = $gallery->thumbnail_url ?? $gallery->images->first()?->url)
                                    @if ($cover)
                                        <img src="{{ $cover }}" class="rounded me-2" width="56" height="42" style="object-fit: cover" alt="">
                                    @endif
                                    <div>
                                        <div>{{ \Illuminate\Support\Str::limit($gallery->title, 50) }}</div>
                                        <small class="text-muted" dir="auto">{{ \Illuminate\Support\Str::limit($gallery->slug, 40) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gallery-thumbs">
                                    @foreach ($gallery->images->take(4) as $image)
                                        <img src="{{ $image->url }}" class="rounded" width="40" height="40" alt="">
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ $gallery->images_count }}</td>
                            <td>{{ $gallery->user?->first_name }} {{ $gallery->user?->last_name }}</td>
                            <td dir="ltr" class="text-end">{{ jdate($gallery->created_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d') }}</td>
                            <td>
                                <a href="{{ route('galleries.edit', ['gallery' => $gallery]) }}" title="ویرایش">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="#" title="حذف"
                                   x-on:click.prevent="confirmDelete(() => $wire.deleteGallery({{ $gallery->id }}), @js('گالری «'.\Illuminate\Support\Str::limit($gallery->title, 60).'» با '.$gallery->images_count.' تصویر حذف خواهد شد.'))">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                گالری‌ای پیدا نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->galleries->links() }}
            </div>
        </div>
    </div>
</div>

@assets
<style>
    .gallery-thumbs img {
        object-fit: cover;
        border: 2px solid var(--bs-body-bg);
        margin-inline-start: -10px;
    }

    .gallery-thumbs img:first-child {
        margin-inline-start: 0;
    }
</style>
@endassets
