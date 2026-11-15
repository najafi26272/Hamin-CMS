<?php

namespace Hamin\FormBuilder\Livewire;

use Hamin\FormBuilder\Models\Form;
use Hamin\FormBuilder\Support\SubmissionService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * نمایش و ثبت فرم در سایت. در هر صفحه‌ای قابل استفاده است:
 * <livewire:form-builder.form slug="my-form" />
 */
class FormRenderer extends Component
{
    use WithFileUploads;

    public string $slug;

    public bool $preview = false;

    public array $values = [];

    public array $files = [];

    public bool $submitted = false;

    public function mount(string $slug, bool $preview = false): void
    {
        $this->slug = $slug;
        $this->preview = $preview;
        $this->values = app(SubmissionService::class)->initialValues($this->form(), auth()->user());
    }

    private function form(): Form
    {
        return Form::where('slug', $this->slug)->with('fields')->firstOrFail();
    }

    public function updatedFiles($value, $key): void
    {
        // اعتبارسنجی فوری فایل انتخاب‌شده (نوع و حجم)
        $field = explode('.', $key)[0];
        $service = app(SubmissionService::class);
        $form = $this->form();

        $rules = collect($service->rules($form))
            ->filter(fn ($r, $k) => $k === 'files.'.$field || $k === 'files.'.$field.'.*')
            ->map(fn ($r) => array_values(array_filter($r, fn ($rule) => $rule !== 'required')))
            ->all();

        if ($rules) {
            $this->validate($rules, $service->messages(), $service->attributes($form));
        }
    }

    public function removeFile(string $field, ?int $index = null): void
    {
        if ($index === null) {
            unset($this->files[$field]);
        } else {
            unset($this->files[$field][$index]);
            $this->files[$field] = array_values($this->files[$field]);
        }
    }

    public function submit(SubmissionService $service): void
    {
        $key = 'form-builder:'.$this->slug.':'.(auth()->id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['form' => 'تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید.']);
        }

        RateLimiter::hit($key, 60);

        $service->submit($this->form(), $this->values, $this->files, auth()->user(), request()->ip(), request()->userAgent());

        $this->submitted = true;
        $this->files = [];
        $this->dispatch('form-builder-submitted', slug: $this->slug);
    }

    public function again(): void
    {
        $this->submitted = false;
        $this->values = app(SubmissionService::class)->initialValues($this->form(), auth()->user());
    }

    public function render(SubmissionService $service)
    {
        $form = $this->form();
        $user = auth()->user();

        $denial = $this->preview && ! $form->is_active
            ? null
            : $service->denialReason($form, $user);

        return view('form-builder::form.renderer', [
            'form' => $form,
            'denial' => $this->submitted ? null : $denial,
            'needsLogin' => $form->needsLogin() && ! $user,
            'missing' => $user ? $service->missingProfileFields($form, $user) : [],
        ]);
    }
}
