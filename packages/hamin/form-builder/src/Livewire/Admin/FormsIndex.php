<?php

namespace Hamin\FormBuilder\Livewire\Admin;

use Hamin\FormBuilder\Models\Form;
use Livewire\Component;
use Livewire\WithPagination;

// لیست فرم‌ها در پنل مدیریت
class FormsIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $form = Form::findOrFail($id);
        $form->update(['is_active' => ! $form->is_active]);

        $this->dispatch('notify', type: 'success', message: $form->is_active ? 'فرم فعال شد.' : 'فرم غیرفعال شد.');
    }

    public function duplicate(int $id): void
    {
        $source = Form::with('fields')->findOrFail($id);

        $copy = $source->replicate(['slug']);
        $copy->title = $source->title.' (کپی)';
        $copy->slug = Form::makeSlug($copy->title);
        $copy->is_active = false;
        $copy->save();

        foreach ($source->fields as $field) {
            $copy->fields()->create($field->replicate(['form_id'])->toArray());
        }

        $this->dispatch('notify', type: 'success', message: 'یک نسخه غیرفعال از فرم ساخته شد.');
    }

    public function delete(int $id): void
    {
        Form::findOrFail($id)->delete();

        $this->dispatch('notify', type: 'success', message: 'فرم حذف شد.');
    }

    public function render()
    {
        $search = trim($this->search);

        $forms = Form::query()
            ->withCount(['fields', 'submissions'])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->latest('id')
            ->paginate(15);

        return view('form-builder::admin.forms-index', compact('forms'))
            ->layout(config('form-builder.admin.layout'))
            ->title('فرم‌ساز');
    }
}
