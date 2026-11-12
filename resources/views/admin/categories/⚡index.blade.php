<?php

use App\Models\Category;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('لیست دسته‌بندی‌ها')] class extends Component
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
    public function categories()
    {
        $term = trim($this->search);

        return Category::query()
            ->with('parent')
            ->withCount(['children', 'posts'])
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like));
            })
            ->latest('id')
            ->paginate(10);
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::find($id);

        if (! $category) {
            $this->dispatch('notify', type: 'error', message: 'دسته‌بندی مورد نظر پیدا نشد.');
            return;
        }

        // زیرمجموعه‌ها به والد دسته‌بندی حذف شده منتقل می‌شوند
        Category::where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);

        $category->delete(); // ارتباط با مطالب با cascade حذف می‌شود

        $this->dispatch('notify', type: 'success', message: 'دسته‌بندی با موفقیت حذف شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">لیست دسته‌بندی‌ها</h5>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">ثبت دسته‌بندی جدید</a>
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
                           placeholder="نام یا slug دسته‌بندی">
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
                        <th>والد</th>
                        <th>زیرمجموعه‌ها</th>
                        <th>مطالب</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->categories as $category)
                        <tr wire:key="category-{{ $category->id }}">
                            <td>{{ $this->categories->firstItem() + $loop->index }}</td>
                            <td>{{ $category->name }}</td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td>
                                @if ($category->parent)
                                    <span class="badge bg-secondary">{{ $category->parent->name }}</span>
                                @else
                                    <span class="text-muted">— اصلی —</span>
                                @endif
                            </td>
                            <td>{{ $category->children_count }}</td>
                            <td>{{ $category->posts_count }}</td>
                            <td>
                                <a href="{{ route('categories.edit', ['category' => $category]) }}" title="ویرایش">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="#" title="حذف"
                                   x-on:click.prevent="confirmDelete(() => $wire.deleteCategory({{ $category->id }}), @js('دسته‌بندی «'.$category->name.'» حذف خواهد شد.'.($category->children_count ? ' زیرمجموعه‌های آن به '.($category->parent ? '«'.$category->parent->name.'»' : 'دسته‌بندی اصلی').' منتقل می‌شوند.' : '')))">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                دسته‌بندی پیدا نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->categories->links() }}
            </div>
        </div>
    </div>
</div>
