<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    #[Url(history: true)]
    public string $status = '';

    public function updatedFirstName(): void
    {
        $this->resetPage();
    }

    public function updatedLastName(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('firstName', 'lastName', 'status');
        $this->resetPage();
    }

    // تعداد کاربران در هر وضعیت درخواست هنرمندی (برای تب‌ها)
    #[Computed]
    public function counts(): array
    {
        $counts = User::selectRaw('artist_status, count(*) as total')->groupBy('artist_status')->pluck('total', 'artist_status');

        return ['' => $counts->sum()] + collect(User::ARTIST_STATUSES)->map(fn ($label, $key) => $counts[$key] ?? 0)->all();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['roles', 'artSpecialties'])
            ->when($this->status !== '', fn ($q) => $q->where('artist_status', $this->status))
            ->when(trim($this->firstName) !== '', fn($q) => $q->where('first_name', 'like', '%' . $this->escapeLike(trim($this->firstName)) . '%'))
            ->when(trim($this->lastName) !== '', fn($q) => $q->where('last_name', 'like', '%' . $this->escapeLike(trim($this->lastName)) . '%'))
            ->latest('id')
            ->paginate(10);
    }

    // تأیید درخواست هنرمندی: کاربر نقش «هنرمند» می‌گیرد و می‌تواند مطلب ارسال کند
    public function approveUser(int $id): void
    {
        if (Gate::denies('manage-users')) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی تأیید کاربران را ندارید.');
            return;
        }

        $user = User::find($id);

        if (! $user || ! in_array($user->artist_status, ['pending', 'rejected'], true)) {
            $this->dispatch('notify', type: 'error', message: 'این کاربر درخواست هنرمندی قابل تأییدی ندارد.');
            return;
        }

        DB::transaction(function () use ($user) {
            $user->update([
                'artist_status' => 'approved',
                'artist_rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $user->roles()->syncWithoutDetaching([Role::firstOrCreate(['name' => 'artist'], ['label' => 'هنرمند'])->id]);
        });

        $this->dispatch('notify', type: 'success', message: 'درخواست هنرمندی تأیید شد.');
    }

    // رد درخواست هنرمندی با ثبت توضیحات (نقش هنرمند هم گرفته می‌شود)
    public function rejectUser(int $id, string $reason = ''): void
    {
        if (Gate::denies('manage-users')) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی رد کاربران را ندارید.');
            return;
        }

        $user = User::find($id);

        if (! $user || ! in_array($user->artist_status, ['pending', 'approved'], true)) {
            $this->dispatch('notify', type: 'error', message: 'این کاربر درخواست هنرمندی قابل ردی ندارد.');
            return;
        }

        if ($id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'امکان رد درخواست خودتان وجود ندارد.');
            return;
        }

        $reason = trim($reason);

        if (mb_strlen($reason) > 1000) {
            $this->dispatch('notify', type: 'error', message: 'توضیحات نباید بیشتر از ۱۰۰۰ کاراکتر باشد.');
            return;
        }

        DB::transaction(function () use ($user, $reason) {
            $user->update([
                'artist_status' => 'rejected',
                'artist_rejection_reason' => $reason ?: null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $artistRoleId = Role::where('name', 'artist')->value('id');
            if ($artistRoleId) {
                $user->roles()->detach($artistRoleId);
            }
        });

        $this->dispatch('notify', type: 'success', message: 'درخواست هنرمندی رد شد.');
    }

    public function deleteUser(int $id): void
    {
        if (Gate::denies('manage-users')) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی حذف کاربران را ندارید.');
            return;
        }

        $user = User::find($id);

        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'کاربر مورد نظر پیدا نشد.');
            return;
        }

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'امکان حذف حساب کاربری خودتان وجود ندارد.');
            return;
        }

        $user->delete();

        $this->dispatch('notify', type: 'success', message: 'کاربر با موفقیت حذف شد.');
    }

    // ---------- نمایش اطلاعات کاربر در مودال ----------

    public ?int $viewingId = null;

    public function showUser(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeUser(): void
    {
        $this->viewingId = null;
    }

    #[Computed]
    public function viewingUser(): ?User
    {
        return $this->viewingId
            ? User::with(['roles', 'artSpecialties', 'reviewer'])->find($this->viewingId)
            : null;
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

        @can('manage-users')
            <a href="{{route('users.create')}}" class="btn btn-primary">
                ثبت کاربر جدید
            </a>
        @endcan
    </div>

    {{-- تب‌های وضعیت درخواست هنرمندی --}}
    <ul class="nav nav-pills mb-3 gap-2">
        @foreach (['' => 'همه کاربران'] + \App\Models\User::ARTIST_STATUSES as $key => $label)
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
                        <th>هنرمندی</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->users as $user)
                        <tr wire:key="user-{{ $user->id }}" wire:click="showUser({{ $user->id }})" class="user-row" title="نمایش اطلاعات کاربر">
                            <td>{{ $this->users->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ $user->avatar_url }}" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover" alt="">
                                    <div>
                                        <div>{{ $user->first_name }} {{ $user->last_name }}</div>
                                        @if ($user->artSpecialties->isNotEmpty())
                                            <small class="text-muted">{{ $user->artSpecialties->pluck('name')->join('، ') }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->mobile ?? '—' }}</td>
                            <td>{{ $user->email ?? '—' }}</td>
                            <td>{{ $user->city ?? '—' }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->label ?? $role->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td>
                                @if ($user->artist_status === 'pending')
                                    <span class="badge bg-warning text-dark">در انتظار بررسی</span>
                                @elseif ($user->artist_status === 'rejected')
                                    <span class="badge bg-danger">رد شده</span>
                                    @if ($user->artist_rejection_reason)
                                        <div class="small text-muted mt-1 rejection-reason" title="{{ $user->artist_rejection_reason }}">{{ \Illuminate\Support\Str::limit($user->artist_rejection_reason, 40) }}</div>
                                    @endif
                                @elseif ($user->artist_status === 'approved')
                                    <span class="badge bg-success">هنرمند</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            {{-- کلیک روی دکمه‌های عملیات، مودال را باز نکند --}}
                            <td class="text-nowrap" x-on:click.stop>
                                @can('manage-users')
                                    @if (in_array($user->artist_status, ['pending', 'rejected'], true))
                                        <a href="#" title="تأیید درخواست هنرمندی" wire:click.prevent="approveUser({{ $user->id }})">
                                            <i class="text-success list-icon" data-feather="check-circle"></i>
                                        </a>
                                    @endif

                                    @if (in_array($user->artist_status, ['pending', 'approved'], true) && $user->id !== auth()->id())
                                        <a href="#" title="رد درخواست هنرمندی"
                                           x-on:click.prevent="askReason(@js('رد درخواست هنرمندی «'.$user->first_name.' '.$user->last_name.'»'), (reason) => $wire.rejectUser({{ $user->id }}, reason))">
                                            <i class="text-warning list-icon" data-feather="x-circle"></i>
                                        </a>
                                    @endif
                                @endcan

                                @can('edit-users')
                                    <a href="{{route('users.edit' , ['user'=>$user])}}" title="ویرایش">
                                        <i class="list-icon" data-feather="edit"></i>
                                    </a>
                                @endcan

                                @can('manage-users')
                                    <a href="#" title="حذف"
                                       x-on:click.prevent="confirmDelete(() => $wire.deleteUser({{ $user->id }}), @js('کاربر «'.$user->first_name.' '.$user->last_name.'» حذف خواهد شد.'))">
                                        <i class="text-danger list-icon" data-feather="trash"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
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

    {{-- مودال اطلاعات کاربر --}}
    @if ($u = $this->viewingUser)
        <div class="modal d-block" tabindex="-1" wire:key="user-modal-{{ $u->id }}"
             x-data x-on:keydown.escape.window="$wire.closeUser()" x-on:click.self="$wire.closeUser()">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">اطلاعات کاربر</h5>
                        <button type="button" class="btn-close" wire:click="closeUser" aria-label="بستن"></button>
                    </div>

                    <div class="modal-body">
                        {{-- سربرگ --}}
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img src="{{ $u->avatar_url }}" class="rounded-circle" width="88" height="88" style="object-fit: cover" alt="">
                            <div>
                                <h5 class="mb-1">{{ $u->first_name }} {{ $u->last_name }}</h5>
                                <div class="text-muted mb-2" dir="ltr" style="text-align: right">{{ '@'.$u->username }}</div>
                                @if ($u->artist_status === 'pending')
                                    <span class="badge bg-warning text-dark">درخواست هنرمندی در انتظار بررسی</span>
                                @elseif ($u->artist_status === 'rejected')
                                    <span class="badge bg-danger">درخواست هنرمندی رد شده</span>
                                @elseif ($u->artist_status === 'approved')
                                    <span class="badge bg-success">هنرمند تأییدشده</span>
                                @else
                                    <span class="badge bg-secondary">کاربر عادی</span>
                                @endif
                                @foreach ($u->roles as $role)
                                    <span class="badge bg-primary">{{ $role->label ?? $role->name }}</span>
                                @endforeach
                            </div>
                        </div>

                        @if ($u->artist_status === 'rejected' && $u->artist_rejection_reason)
                            <div class="alert alert-danger py-2">
                                <strong>دلیل رد:</strong> {{ $u->artist_rejection_reason }}
                            </div>
                        @endif

                        @if ($u->artSpecialties->isNotEmpty())
                            <div class="mb-3">
                                <div class="text-muted small mb-1">تخصص‌های هنری</div>
                                @foreach ($u->artSpecialties as $specialty)
                                    <span class="badge bg-secondary">{{ $specialty->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @php
                            $tehran = fn ($date) => $date ? jdate($date->copy()->setTimezone('Asia/Tehran')) : null;
                            $details = [
                                ['user', 'تاریخ تولد', $u->birth_date ? jdate($u->birth_date)->format('Y/m/d').' ('.$u->birth_date->age.' ساله)' : null],
                                ['award', 'مدرک تحصیلی', $u->education_degree],
                                ['map-pin', 'شهر محل سکونت', $u->city],
                                ['smartphone', 'شماره موبایل', $u->mobile],
                                ['phone', 'شماره تماس ثابت', $u->phone],
                                ['message-circle', 'شماره فضای مجازی', $u->virtual_space_number],
                                ['mail', 'ایمیل', $u->email],
                                ['instagram', 'اینستاگرام', $u->instagram_id],
                                ['clock', 'تاریخ ثبت‌نام', $tehran($u->created_at)?->format('Y/m/d H:i')],
                                ['check-square', 'بررسی شده توسط', $u->reviewer ? $u->reviewer->first_name.' '.$u->reviewer->last_name.' — '.$tehran($u->reviewed_at)?->format('Y/m/d H:i') : null],
                            ];
                        @endphp

                        <div class="row g-3">
                            @foreach ($details as [$icon, $label, $value])
                                <div class="col-md-6">
                                    <div class="user-detail">
                                        <span class="user-detail-icon" wire:ignore><i data-feather="{{ $icon }}"></i></span>
                                        <div class="min-w-0">
                                            <div class="text-muted small">{{ $label }}</div>
                                            @if ($value && $label === 'اینستاگرام')
                                                <a href="https://instagram.com/{{ $value }}" target="_blank" rel="noopener" dir="ltr">{{ '@'.$value }}</a>
                                            @elseif ($value && $label === 'ایمیل')
                                                <a href="mailto:{{ $value }}" dir="ltr">{{ $value }}</a>
                                            @else
                                                <div class="text-break">{{ $value ?: '—' }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer">
                        @can('manage-users')
                            @if (in_array($u->artist_status, ['pending', 'rejected'], true))
                                <button type="button" class="btn btn-success" wire:click="approveUser({{ $u->id }})">تأیید هنرمند</button>
                            @endif
                            @if (in_array($u->artist_status, ['pending', 'approved'], true) && $u->id !== auth()->id())
                                <button type="button" class="btn btn-warning"
                                        x-on:click="askReason(@js('رد درخواست هنرمندی «'.$u->first_name.' '.$u->last_name.'»'), (reason) => $wire.rejectUser({{ $u->id }}, reason))">رد درخواست</button>
                            @endif
                        @endcan
                        @can('edit-users')
                            <a href="{{ route('users.edit', ['user' => $u]) }}" class="btn btn-primary">ویرایش</a>
                        @endcan
                        <button type="button" class="btn btn-secondary" wire:click="closeUser">بستن</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop show"></div>
    @endif
</div>

@assets
<style>
    .user-row {
        cursor: pointer;
    }

    .user-detail {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 10px 12px;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        height: 100%;
    }

    .user-detail-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: rgba(101, 113, 255, .12);
        color: #6571ff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .user-detail-icon svg {
        width: 17px;
        height: 17px;
    }

    .min-w-0 {
        min-width: 0;
    }
</style>
@endassets
