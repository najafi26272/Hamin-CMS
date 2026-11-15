<?php

namespace Hamin\FormBuilder\Models;

use Hamin\FormBuilder\Support\FieldTypes;
use Hamin\FormBuilder\Support\ProfileFields;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $table = 'form_builder_fields';

    protected $fillable = [
        'form_id', 'kind', 'type', 'name', 'label', 'placeholder', 'help',
        'is_required', 'is_readonly', 'width', 'options', 'settings', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_readonly' => 'boolean',
            'options' => 'array',
            'settings' => 'array',
        ];
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function isProfile(): bool
    {
        return $this->kind === 'profile';
    }

    // فیلد پروفایل غیرقابل ویرایش: مقدار فقط از پایگاه داده خوانده می‌شود
    public function isLocked(): bool
    {
        return $this->isProfile() && $this->is_readonly;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $value = $this->settings[$key] ?? null;

        return $value === null || $value === '' ? ($default ?? FieldTypes::defaults($this->type)[$key] ?? null) : $value;
    }

    // نوع ورودی HTML (برای فیلد پروفایل از config خوانده می‌شود)
    public function inputType(): string
    {
        return $this->isProfile() ? (ProfileFields::get($this->type)['input'] ?? 'text') : $this->type;
    }

    // قالب اعتبارسنجی متن (email, mobile, national_code, ...)
    public function format(): ?string
    {
        return $this->isProfile() ? (ProfileFields::get($this->type)['format'] ?? null) : ($this->setting('format') ?: null);
    }

    public function optionList(): array
    {
        return array_values(array_filter(array_map('trim', (array) $this->options), fn ($o) => $o !== ''));
    }

    // چک‌باکس بدون گزینه = یک تیک تکی (مثل «قوانین را می‌پذیرم»)
    public function isSingleCheckbox(): bool
    {
        return $this->type === 'checkbox' && ! $this->isProfile() && count($this->optionList()) === 0;
    }

    // آیا مقدار این فیلد آرایه است؟ (چندانتخابی، گروه چک‌باکس، چند فایل)
    public function isMultiple(): bool
    {
        return match ($this->type) {
            'select' => (bool) $this->setting('multiple'),
            'checkbox' => ! $this->isSingleCheckbox(),
            'file' => (bool) $this->setting('multiple'),
            default => false,
        };
    }
}
