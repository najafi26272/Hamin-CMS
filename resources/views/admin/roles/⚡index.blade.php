<?php

use App\Models\Role;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('لیست نقش‌ها')] class extends Component
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
    public function roles()
    {
        $term = trim($this->search);

        return Role::query()
            ->with('permissions')
            ->withCount('users')
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
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">لیست نقش‌ها</h5>
        <a href="{{route('roles.create')}}" class="btn btn-primary">ثبت نقش جدید</a>
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
                           placeholder="نام فنی یا عنوان نقش">
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
                        <th>عنوان نقش</th>
                        <th>نام فنی</th>
                        <th>مجوزها</th>
                        <th>تعداد کاربران</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td>{{ $this->roles->firstItem() + $loop->index }}</td>
                            <td>{{ $role->label ?? '—' }}</td>
                            <td><code>{{ $role->name }}</code></td>
                            <td>
                                @forelse ($role->permissions as $permission)
                                    <span class="badge bg-info text-dark">
                                            {{ $permission->label ?? $permission->name }}
                                        </span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                <a href="{{route("roles.edit" , ['role'=>$role])}}">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="#">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                نقشی پیدا نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->roles->links() }}
            </div>
        </div>
    </div>
</div>
