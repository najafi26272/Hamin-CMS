<?php

namespace Hamin\FormBuilder\Livewire\Admin;

use Hamin\FormBuilder\Models\Form;
use Hamin\FormBuilder\Models\FormField;
use Hamin\FormBuilder\Support\FieldTypes;
use Hamin\FormBuilder\Support\ProfileFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

// ساخت و ویرایش فرم: تنظیمات فرم + لیست فیلدها (همه با یک دکمه ذخیره می‌شوند)
class FormEditor extends Component
{
    public ?int $formId = null;

    public array $info = [
        'title' => '',
        'slug' => '',
        'description' => '',
        'is_active' => false,
        'requires_auth' => false,
        'allow_multiple' => true,
        'submit_label' => '',
        'success_message' => '',
    ];

    /** @var array<int, array> */
    public array $fields = [];

    // فیلدی که پنل تنظیماتش باز است
    public ?string $openUid = null;

    public function mount(Form $form): void
    {
        if (! $form->exists) {
            return;
        }

        $this->formId = $form->id;
        $this->info = [
            'title' => $form->title,
            'slug' => $form->slug,
            'description' => (string) $form->description,
            'is_active' => $form->is_active,
            'requires_auth' => $form->requires_auth,
            'allow_multiple' => $form->allow_multiple,
            'submit_label' => (string) $form->submit_label,
            'success_message' => (string) $form->success_message,
        ];

        $this->fields = $form->fields->map(fn (FormField $f) => [
            'uid' => 'u'.$f->id,
            'id' => $f->id,
            'kind' => $f->kind,
            'type' => $f->type,
            'name' => $f->name,
            'label' => $f->label,
            'placeholder' => (string) $f->placeholder,
            'help' => (string) $f->help,
            'is_required' => $f->is_required,
            'is_readonly' => $f->is_readonly,
            'width' => $f->width,
            'options' => implode("\n", (array) $f->options),
            'settings' => array_merge(FieldTypes::defaults($f->type), (array) $f->settings),
        ])->all();
    }

    // ---------- افزودن و مرتب‌سازی فیلدها ----------

    public function addField(string $type): void
    {
        abort_unless(array_key_exists($type, FieldTypes::TYPES), 422);

        $uid = 'n'.Str::lower(Str::random(8));
        $this->fields[] = [
            'uid' => $uid,
            'id' => null,
            'kind' => 'input',
            'type' => $type,
            'name' => 'f_'.Str::lower(Str::random(6)),
            'label' => FieldTypes::label($type),
            'placeholder' => '',
            'help' => '',
            'is_required' => false,
            'is_readonly' => false,
            'width' => 'full',
            'options' => in_array($type, ['select', 'radio'], true) ? "گزینه ۱\nگزینه ۲" : '',
            'settings' => FieldTypes::defaults($type),
        ];
        $this->openUid = $uid;
    }

    public function addProfileField(string $key): void
    {
        $config = ProfileFields::get($key);
        abort_unless($config, 422);

        if (collect($this->fields)->contains(fn ($f) => $f['kind'] === 'profile' && $f['type'] === $key)) {
            $this->dispatch('notify', type: 'error', message: 'این فیلد قبلا به فرم اضافه شده است.');
            return;
        }

        $uid = 'n'.Str::lower(Str::random(8));
        $this->fields[] = [
            'uid' => $uid,
            'id' => null,
            'kind' => 'profile',
            'type' => $key,
            'name' => $key,
            'label' => $config['label'],
            'placeholder' => '',
            'help' => '',
            'is_required' => true,
            'is_readonly' => true,
            'width' => 'half',
            'options' => '',
            'settings' => [],
        ];
        $this->openUid = $uid;
    }

    public function removeField(string $uid): void
    {
        $this->fields = array_values(array_filter($this->fields, fn ($f) => $f['uid'] !== $uid));
    }

    public function moveField(string $uid, int $direction): void
    {
        $i = collect($this->fields)->search(fn ($f) => $f['uid'] === $uid);
        $j = $i + ($direction < 0 ? -1 : 1);

        if ($i === false || ! isset($this->fields[$j])) {
            return;
        }

        [$this->fields[$i], $this->fields[$j]] = [$this->fields[$j], $this->fields[$i]];
    }

    public function toggleField(string $uid): void
    {
        $this->openUid = $this->openUid === $uid ? null : $uid;
    }

    public function updatedInfoTitle(string $value): void
    {
        // برای فرم جدید، آدرس همراه عنوان ساخته می‌شود
        if (! $this->formId) {
            $this->info['slug'] = Form::makeSlug($value);
        }
    }

    // ---------- ذخیره ----------

    public function save()
    {
        $this->info['slug'] = trim($this->info['slug']) !== '' ? Form::makeSlug($this->info['slug'], $this->formId) : Form::makeSlug($this->info['title'], $this->formId);

        try {
            $this->validate($this->rules(), $this->messages(), $this->attributes());
            $this->validateFieldLogic();
        } catch (ValidationException $e) {
            // فیلدی که خطا دارد باز شود
            foreach (array_keys($e->errors()) as $key) {
                if (preg_match('/^fields\.(\d+)\./', $key, $m) && isset($this->fields[$m[1]])) {
                    $this->openUid = $this->fields[$m[1]]['uid'];
                    break;
                }
            }
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $isNew = ! $this->formId;

        DB::transaction(function () {
            $form = $this->formId ? Form::findOrFail($this->formId) : new Form;
            $form->fill([
                'title' => trim($this->info['title']),
                'slug' => $this->info['slug'],
                'description' => trim($this->info['description']) ?: null,
                'is_active' => (bool) $this->info['is_active'],
                'requires_auth' => (bool) $this->info['requires_auth'],
                'allow_multiple' => (bool) $this->info['allow_multiple'],
                'submit_label' => trim($this->info['submit_label']) ?: null,
                'success_message' => trim($this->info['success_message']) ?: null,
            ])->save();

            $keep = [];

            foreach (array_values($this->fields) as $order => $f) {
                $data = [
                    'kind' => $f['kind'],
                    'type' => $f['type'],
                    'name' => $f['name'],
                    'label' => trim($f['label']),
                    'placeholder' => trim($f['placeholder']) ?: null,
                    'help' => trim($f['help']) ?: null,
                    'is_required' => (bool) $f['is_required'],
                    'is_readonly' => $f['kind'] === 'profile' && $f['is_readonly'],
                    'width' => $f['width'] === 'half' ? 'half' : 'full',
                    'options' => $this->optionLines($f['options']) ?: null,
                    'settings' => $this->cleanSettings($f) ?: null,
                    'sort_order' => $order + 1,
                ];

                $field = $f['id'] ? $form->fields()->whereKey($f['id'])->first() : null;
                $field ? $field->update($data) : ($field = $form->fields()->create($data));
                $keep[] = $field->id;
            }

            // فیلدهای حذف‌شده (پاسخ‌های قبلی آن‌ها در داده‌های ثبت‌شده باقی می‌مانند)
            $form->fields()->whereNotIn('id', $keep)->delete();

            $this->formId = $form->id;
        });

        if ($isNew) {
            session()->flash('success', 'فرم ساخته شد. حالا می‌توانید آن را فعال کنید و لینکش را منتشر کنید.');

            return $this->redirectRoute('form-builder.admin.forms.edit', ['form' => $this->formId]);
        }

        // شناسه فیلدهای تازه ذخیره‌شده
        $this->mount(Form::with('fields')->findOrFail($this->formId));
        $this->dispatch('notify', type: 'success', message: 'فرم ذخیره شد.');

        return null;
    }

    protected function rules(): array
    {
        return [
            'info.title' => ['required', 'string', 'max:190'],
            'info.slug' => ['required', 'max:100', Rule::unique('form_builder_forms', 'slug')->ignore($this->formId)],
            'info.description' => ['nullable', 'string', 'max:5000'],
            'info.submit_label' => ['nullable', 'string', 'max:60'],
            'info.success_message' => ['nullable', 'string', 'max:1000'],
            'fields' => ['array', 'max:100'],
            'fields.*.label' => ['required', 'string', 'max:190'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:190'],
            'fields.*.help' => ['nullable', 'string', 'max:255'],
            'fields.*.settings.min_length' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'fields.*.settings.max_length' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'fields.*.settings.min' => ['nullable', 'numeric'],
            'fields.*.settings.max' => ['nullable', 'numeric'],
            'fields.*.settings.rows' => ['nullable', 'integer', 'min:2', 'max:20'],
            'fields.*.settings.max_size_kb' => ['nullable', 'integer', 'min:1', 'max:'.config('form-builder.uploads.max_size_kb', 20480)],
            'fields.*.settings.max_files' => ['nullable', 'integer', 'min:1', 'max:'.config('form-builder.uploads.max_files', 10)],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute بیش از حد مجاز است.',
            'min' => ':attribute کمتر از حد مجاز است.',
            'integer' => ':attribute باید عدد صحیح باشد.',
            'numeric' => ':attribute باید عدد باشد.',
            'unique' => 'این آدرس قبلا برای فرم دیگری استفاده شده است.',
            'fields.max' => 'حداکثر ۱۰۰ فیلد مجاز است.',
            'fields.*.settings.max_size_kb.max' => 'حداکثر حجم مجاز :max کیلوبایت است.',
            'fields.*.settings.max_files.max' => 'حداکثر :max فایل مجاز است.',
        ];
    }

    protected function attributes(): array
    {
        return [
            'info.title' => 'عنوان فرم',
            'info.slug' => 'آدرس فرم',
            'info.description' => 'توضیحات',
            'info.submit_label' => 'متن دکمه',
            'info.success_message' => 'پیام موفقیت',
            'fields.*.label' => 'عنوان فیلد',
            'fields.*.placeholder' => 'متن راهنمای داخل فیلد',
            'fields.*.help' => 'توضیح زیر فیلد',
            'fields.*.settings.min_length' => 'حداقل طول',
            'fields.*.settings.max_length' => 'حداکثر طول',
            'fields.*.settings.min' => 'حداقل مقدار',
            'fields.*.settings.max' => 'حداکثر مقدار',
            'fields.*.settings.rows' => 'تعداد سطر',
            'fields.*.settings.max_size_kb' => 'حداکثر حجم',
            'fields.*.settings.max_files' => 'حداکثر تعداد فایل',
        ];
    }

    // قواعدی که به نوع هر فیلد بستگی دارند
    private function validateFieldLogic(): void
    {
        $errors = [];

        foreach ($this->fields as $i => $f) {
            $options = $this->optionLines($f['options']);
            $s = $f['settings'] ?? [];

            if (in_array($f['type'], ['select', 'radio'], true) && $f['kind'] === 'input' && count($options) < 2) {
                $errors["fields.$i.options"] = 'حداقل دو گزینه وارد کنید (هر گزینه در یک سطر).';
            }

            if (count($options) !== count(array_unique($options))) {
                $errors["fields.$i.options"] = 'گزینه تکراری وارد شده است.';
            }

            if ($f['type'] === 'file' && $f['kind'] === 'input' && ! FieldTypes::allowedExtensions((array) ($s['extensions'] ?? []))) {
                $errors["fields.$i.settings.extensions"] = 'حداقل یک نوع فایل مجاز انتخاب کنید.';
            }

            if (is_numeric($s['min'] ?? null) && is_numeric($s['max'] ?? null) && $s['min'] > $s['max']) {
                $errors["fields.$i.settings.max"] = 'حداکثر باید بزرگ‌تر از حداقل باشد.';
            }

            if (is_numeric($s['min_length'] ?? null) && is_numeric($s['max_length'] ?? null) && $s['min_length'] > $s['max_length']) {
                $errors["fields.$i.settings.max_length"] = 'حداکثر طول باید بیشتر از حداقل باشد.';
            }

            if ($f['kind'] === 'profile' && ! ProfileFields::exists($f['type'])) {
                $errors["fields.$i.label"] = 'این فیلد پروفایل در تنظیمات پروژه تعریف نشده است.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function optionLines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text)), fn ($l) => $l !== ''));
    }

    // فقط تنظیمات مربوط به همان نوع ذخیره می‌شود
    private function cleanSettings(array $f): array
    {
        if ($f['kind'] === 'profile') {
            return [];
        }

        $allowed = array_keys(FieldTypes::defaults($f['type']));
        $settings = array_intersect_key((array) $f['settings'], array_flip($allowed));

        foreach ($settings as $key => $value) {
            if (in_array($key, ['multiple', 'integer', 'inline'], true)) {
                $settings[$key] = (bool) $value;
            } elseif ($key === 'extensions') {
                $settings[$key] = FieldTypes::allowedExtensions((array) $value);
            } elseif ($value === '' || $value === null) {
                $settings[$key] = null;
            } elseif (is_numeric($value)) {
                $settings[$key] = $value + 0;
            }
        }

        return $settings;
    }

    public function render()
    {
        $added = collect($this->fields)->where('kind', 'profile')->pluck('type')->all();

        return view('form-builder::admin.form-editor', [
            'types' => FieldTypes::TYPES,
            'formats' => FieldTypes::TEXT_FORMATS,
            'profileFields' => ProfileFields::all(),
            'addedProfile' => $added,
            'extensions' => array_values(array_diff(config('form-builder.uploads.allowed_extensions', []), FieldTypes::BLOCKED_EXTENSIONS)),
            'publicUrl' => $this->formId && \Illuminate\Support\Facades\Route::has('form-builder.show') && $this->info['slug']
                ? route('form-builder.show', $this->info['slug']) : null,
        ])->layout(config('form-builder.admin.layout'))->title($this->formId ? 'ویرایش فرم' : 'فرم جدید');
    }
}
