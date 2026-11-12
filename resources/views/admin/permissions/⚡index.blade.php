<?php

use App\Models\Permission;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('لیست مجوزها')] class extends Component
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
    public function permissions()
    {
        $term = trim($this->search);

        return Permission::query()
            ->with('roles')
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('label', 'like', $like));
            })
            ->orderBy('id')
            ->paginate(10);
    }
};
?>

<div>
    <div class="mb-3">
        <h5 class="mb-1">لیست مجوزها</h5>
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
                           placeholder="نام فنی یا عنوان مجوز">
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
                        <th>عنوان مجوز</th>
                        <th>نام فنی</th>
                        <th>توضیحات</th>
                        <th>نقش‌ها</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->permissions as $permission)
                        <tr wire:key="permission-{{ $permission->id }}">
                            <td>{{ $this->permissions->firstItem() + $loop->index }}</td>
                            <td>{{ $permission->label ?? '—' }}</td>
                            <td><code>{{ $permission->name }}</code></td>
                            <td class="text-muted">{{ $permission->description ?: '—' }}</td>
                            <td>
                                @forelse ($permission->roles as $role)
                                    <span class="badge bg-primary">{{ $role->label ?? $role->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                مجوزی پیدا نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->permissions->links() }}
            </div>
        </div>
    </div>
</div>
