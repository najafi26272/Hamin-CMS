<?php

use App\Models\Slider;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('اسلایدرها')] class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(history: true)]
    public string $status = ''; // '' | active | inactive

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sliders()
    {
        return Slider::query()
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function toggleActive(int $id): void
    {
        $slider = Slider::findOrFail($id);
        $slider->update(['is_active' => ! $slider->is_active]);

        $this->dispatch('notify', type: 'success', message: $slider->is_active ? 'اسلایدر فعال شد.' : 'اسلایدر غیرفعال شد.');
    }

    public function deleteSlider(int $id): void
    {
        $slider = Slider::find($id);

        if (! $slider) {
            $this->dispatch('notify', type: 'error', message: 'اسلایدر مورد نظر پیدا نشد.');
            return;
        }

        $slider->delete(); // فایل بنر هم در مدل پاک می‌شود

        $this->dispatch('notify', type: 'success', message: 'اسلایدر با موفقیت حذف شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">اسلایدرها</h5>
        <a href="{{ route('sliders.create') }}" class="btn btn-primary">ثبت اسلایدر جدید</a>
    </div>

    <ul class="nav nav-pills mb-3 gap-2">
        @foreach (['' => 'همه', 'active' => 'فعال', 'inactive' => 'غیرفعال'] as $key => $label)
            <li class="nav-item">
                <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['nav-link', 'active' => $status === $key])>{{ $label }}</button>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive" wire:loading.class="opacity-50">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>ترتیب</th>
                        <th>بنر</th>
                        <th>عنوان</th>
                        <th>لینک</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($this->sliders as $slider)
                        <tr wire:key="slider-{{ $slider->id }}">
                            <td>{{ $slider->sort_order }}</td>
                            <td>
                                <img src="{{ $slider->image_url }}" class="slider-thumb" alt="">
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($slider->title, 50) }}</td>
                            <td>
                                @if ($slider->link)
                                    <a href="{{ $slider->link }}" target="_blank" rel="noopener" dir="ltr" class="small">{{ \Illuminate\Support\Str::limit($slider->link, 35) }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="active-{{ $slider->id }}"
                                           @checked($slider->is_active) wire:click="toggleActive({{ $slider->id }})">
                                    <label class="form-check-label small" for="active-{{ $slider->id }}">{{ $slider->is_active ? 'فعال' : 'غیرفعال' }}</label>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('sliders.edit', ['slider' => $slider]) }}" title="ویرایش">
                                    <i class="list-icon" data-feather="edit"></i>
                                </a>

                                <a href="#" title="حذف"
                                   x-on:click.prevent="confirmDelete(() => $wire.deleteSlider({{ $slider->id }}), @js('اسلایدر «'.\Illuminate\Support\Str::limit($slider->title, 60).'» و فایل بنر آن حذف خواهند شد.'))">
                                    <i class="text-danger list-icon" data-feather="trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">اسلایدری پیدا نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->sliders->links() }}
            </div>
        </div>
    </div>
</div>

