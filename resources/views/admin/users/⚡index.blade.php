<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new  #[Layout('layouts::admin')] class extends Component {
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'first_name', history: true)]
    public string $firstName = '';

    #[Url(as: 'last_name', history: true)]
    public string $lastName = '';

    public function updatedFirstName(): void
    {
        $this->resetPage();
    }

    public function updatedLastName(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('firstName', 'lastName');
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when(trim($this->firstName) !== '', fn($q) => $q->where('first_name', 'like', '%' . $this->escapeLike(trim($this->firstName)) . '%'))
            ->when(trim($this->lastName) !== '', fn($q) => $q->where('last_name', 'like', '%' . $this->escapeLike(trim($this->lastName)) . '%'))
            ->latest('id')
            ->paginate(10);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">لیست کاربران</h5>

        <a href="{{route('users.create')}}" class="btn btn-primary">
            ثبت کاربر جدید
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="firstName" class="form-label">جستجوی نام</label>
                    <input type="text"
                           id="firstName"
                           wire:model.live.debounce.400ms="firstName"
                           class="form-control"
                           placeholder="نام">
                </div>

                <div class="col-md-4">
                    <label for="lastName" class="form-label">جستجوی نام خانوادگی</label>
                    <input type="text"
                           id="lastName"
                           wire:model.live.debounce.400ms="lastName"
                           class="form-control"
                           placeholder="نام خانوادگی">
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
                        <th>نام و نام خانوادگی</th>
                        <th>نام کاربری</th>
                        <th>موبایل</th>
                        <th>ایمیل</th>
                        <th>شهر</th>
                        <th>نقش‌ها</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>{{ $this->users->firstItem() + $loop->index }}</td>
                            <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->mobile }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->city ?? '—' }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->label ?? $role->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td>
                                <a href="{{route('users.edit' , ['user'=>$user])}}">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="{{route('users.edit' , ['user'=>$user])}}">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                کاربری پیدا نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->users->links() }}
            </div>
        </div>
    </div>
</div>
