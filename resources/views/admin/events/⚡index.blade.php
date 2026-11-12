<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('رویدادها')] class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = '';  // '' | active | inactive

    #[Url(history: true)]
    public string $timing = '';  // '' | upcoming | ongoing | finished

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'timing'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'timing');
        $this->resetPage();
    }

    #[Computed]
    public function events()
    {
        $term = trim($this->search);

        return Event::query()
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('slug', 'like', $like));
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->timing === 'upcoming', fn ($q) => $q->where('starts_at', '>', now()))
            ->when($this->timing === 'ongoing', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))
            ->when($this->timing === 'finished', fn ($q) => $q->where('ends_at', '<', now()))
            ->orderByDesc('starts_at')
            ->paginate(10);
    }

    public function toggleActive(int $id): void
    {
        $event = Event::findOrFail($id);
        $event->update(['is_active' => ! $event->is_active]);

        $this->dispatch('notify', type: 'success', message: $event->is_active ? 'رویداد فعال شد.' : 'رویداد غیرفعال شد.');
    }

    public function deleteEvent(int $id): void
    {
        $event = Event::find($id);

        if (! $event) {
            $this->dispatch('notify', type: 'error', message: 'رویداد مورد نظر پیدا نشد.');
            return;
        }

        $event->delete();

        $this->dispatch('notify', type: 'success', message: 'رویداد با موفقیت حذف شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">رویدادها</h5>
        <a href="{{ route('events.create') }}" class="btn btn-primary">ثبت رویداد جدید</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">جستجو</label>
                    <input type="text" id="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="عنوان یا slug رویداد">
                </div>

                <div class="col-md-3">
                    <label for="timing" class="form-label">زمان برگزاری</label>
                    <select id="timing" wire:model.live="timing" class="form-select">
                        <option value="">همه</option>
                        <option value="upcoming">پیش رو</option>
                        <option value="ongoing">در حال برگزاری</option>
                        <option value="finished">پایان یافته</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label">وضعیت</label>
                    <select id="status" wire:model.live="status" class="form-select">
                        <option value="">همه</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                    </select>
                </div>

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
                        <th>تاریخ شروع</th>
                        <th>تاریخ پایان</th>
                        <th>زمان برگزاری</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->events as $event)
                        <tr wire:key="event-{{ $event->id }}">
                            <td>{{ $this->events->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if ($event->poster_url)
                                        <img src="{{ $event->poster_url }}" class="rounded me-2" width="40" height="52" style="object-fit: cover" alt="">
                                    @endif
                                    <div>
                                        <div>{{ \Illuminate\Support\Str::limit($event->title, 50) }}</div>
                                        <small class="text-muted" dir="auto">{{ \Illuminate\Support\Str::limit($event->slug, 40) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><bdi>{{ $event->jalali('starts_at') }}</bdi></td>
                            <td><bdi>{{ $event->jalali('ends_at') }}</bdi></td>
                            <td>
                                @if ($event->timing === 'upcoming')
                                    <span class="badge bg-info text-dark">پیش رو</span>
                                @elseif ($event->timing === 'ongoing')
                                    <span class="badge bg-success">در حال برگزاری</span>
                                @else
                                    <span class="badge bg-secondary">پایان یافته</span>
                                @endif
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="active-{{ $event->id }}"
                                           @checked($event->is_active) wire:click="toggleActive({{ $event->id }})">
                                    <label class="form-check-label small" for="active-{{ $event->id }}">{{ $event->is_active ? 'فعال' : 'غیرفعال' }}</label>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('events.edit', ['event' => $event]) }}" title="ویرایش">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="#" title="حذف"
                                   x-on:click.prevent="confirmDelete(() => $wire.deleteEvent({{ $event->id }}), @js('رویداد «'.\Illuminate\Support\Str::limit($event->title, 60).'» حذف خواهد شد.'))">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">رویدادی پیدا نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->events->links() }}
            </div>
        </div>
    </div>
</div>
