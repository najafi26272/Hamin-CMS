<?php

namespace Hamin\FormBuilder\Livewire\Admin;

use Hamin\FormBuilder\Models\Form;
use Hamin\FormBuilder\Models\FormSubmission;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

// پاسخ‌های یک فرم: جدول، جزئیات هر پاسخ، دانلود فایل‌ها و حذف
class Submissions extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $formId;

    public ?int $viewingId = null;

    public function mount(Form $form): void
    {
        $this->formId = $form->id;
    }

    public function show(int $id): void
    {
        $this->viewingId = $id;
    }

    public function close(): void
    {
        $this->viewingId = null;
    }

    public function delete(int $id): void
    {
        $submission = FormSubmission::where('form_id', $this->formId)->findOrFail($id);

        // فایل‌های پیوست هم پاک می‌شوند
        $paths = [];
        foreach ((array) $submission->data as $value) {
            if (FormSubmission::isFileValue($value)) {
                array_push($paths, ...array_column($value, 'path'));
            }
        }

        $submission->delete();
        Storage::disk(config('form-builder.storage.disk'))->delete($paths);

        $this->viewingId = null;
        $this->dispatch('notify', type: 'success', message: 'پاسخ حذف شد.');
    }

    public function render()
    {
        $form = Form::with('fields')->findOrFail($this->formId);

        $submissions = $form->submissions()->with('user')->latest('id')->paginate(20);

        // در جدول فقط چند ستون اول نمایش داده می‌شود؛ بقیه در پنجره جزئیات
        $columns = $form->fields->reject(fn ($f) => $f->type === 'file' || $f->type === 'textarea')->take(4);

        $viewing = $this->viewingId ? FormSubmission::with('user')->where('form_id', $form->id)->find($this->viewingId) : null;

        return view('form-builder::admin.submissions', compact('form', 'submissions', 'columns', 'viewing'))
            ->layout(config('form-builder.admin.layout'))
            ->title('پاسخ‌های '.$form->title);
    }
}
