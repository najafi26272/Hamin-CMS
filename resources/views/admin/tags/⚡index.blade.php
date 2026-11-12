<?php

use App\Models\Tag;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('لیست برچسب‌ها')] class extends Component
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
    public function tags()
    {
        $term = trim($this->search);

        return Tag::query()
            ->withCount('posts')
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like));
            })
            ->latest('id')
            ->paginate(10);
    }

    public function deleteTag(int $id): void
    {
        $tag = Tag::find($id);

        if (! $tag) {
            $this->dispatch('notify', type: 'error', message: 'برچسب مورد نظر پیدا نشد.');
            return;
        }

        $tag->delete(); // ارتباط با مطالب با cascade حذف می‌شود

        $this->dispatch('notify', type: 'success', message: 'برچسب با موفقیت حذف شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">لیست برچسب‌ها</h5>
        <a href="{{ route('tags.create') }}" class="btn btn-primary">ثبت برچسب جدید</a>
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
                           placeholder="نام یا slug برچسب">
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
                        <th>نام</th>
                        <th>slug</th>
                        <th>مطالب</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->tags as $tag)
                        <tr wire:key="tag-{{ $tag->id }}">
                            <td>{{ $this->tags->firstItem() + $loop->index }}</td>
                            <td><span class="badge bg-primary">#{{ $tag->name }}</span></td>
                            <td><code>{{ $tag->slug }}</code></td>
                            <td>{{ $tag->posts_count }}</td>
                            <td>
                                <a href="{{ route('tags.edit', ['tag' => $tag]) }}" title="ویرایش">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="#" title="حذف"
                                   x-on:click.prevent="confirmDelete(() => $wire.deleteTag({{ $tag->id }}), @js('برچسب «'.$tag->name.'» حذف'.($tag->posts_count ? ' و از '.$tag->posts_count.' مطلب برداشته' : '').' خواهد شد.'))">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                برچسبی پیدا نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->tags->links() }}
            </div>
        </div>
    </div>
</div>
