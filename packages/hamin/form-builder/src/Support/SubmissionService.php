<?php

namespace Hamin\FormBuilder\Support;

use Hamin\FormBuilder\Models\Form;
use Hamin\FormBuilder\Models\FormField;
use Hamin\FormBuilder\Models\FormSubmission;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * هسته فرم‌ساز (مستقل از رابط کاربری): مقادیر اولیه، اعتبارسنجی و ذخیره پاسخ‌ها.
 * هم کامپوننت Livewire و هم API از همین کلاس استفاده می‌کنند.
 * کلید خطاها: values.{name} برای مقادیر و files.{name} برای فایل‌ها
 */
class SubmissionService
{
    // آیا کاربر می‌تواند این فرم را پر کند؟ پیام خطا یا null
    public function denialReason(Form $form, ?Authenticatable $user): ?string
    {
        if (! $form->is_active) {
            return 'این فرم در حال حاضر فعال نیست.';
        }

        if ($form->needsLogin() && ! $user) {
            return 'برای تکمیل این فرم ابتدا وارد حساب کاربری خود شوید.';
        }

        if ($user && ! $form->allow_multiple && $form->submissions()->where('user_id', $user->getAuthIdentifier())->exists()) {
            return 'شما قبلا این فرم را ثبت کرده‌اید.';
        }

        return null;
    }

    // مقادیر اولیه فرم: فیلدهای پروفایل از پایگاه داده، بقیه خالی
    public function initialValues(Form $form, ?Authenticatable $user): array
    {
        $values = [];

        foreach ($form->fields as $field) {
            if ($field->type === 'file') {
                continue;
            }

            $values[$field->name] = match (true) {
                $field->isProfile() => ProfileFields::value($field->type, $user),
                $field->isSingleCheckbox() => false,
                $field->isMultiple() => [],
                default => '',
            };
        }

        return $values;
    }

    // فیلدهای قفل که در پروفایل کاربر خالی هستند (کاربر باید اول پروفایلش را کامل کند)
    public function missingProfileFields(Form $form, ?Authenticatable $user): array
    {
        return $form->fields
            ->filter(fn (FormField $f) => $f->isLocked() && $f->is_required && ProfileFields::value($f->type, $user) === '')
            ->pluck('label')->all();
    }

    public function rules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            $base = $field->is_required ? ['required'] : ['nullable'];

            if ($field->type === 'file') {
                $fileRules = ['file', 'max:'.$this->fileMaxKb($field)];
                if ($extensions = FieldTypes::allowedExtensions((array) $field->setting('extensions'))) {
                    $fileRules[] = 'extensions:'.implode(',', $extensions);
                    $fileRules[] = 'mimes:'.implode(',', $this->mimesFor($extensions));
                } else {
                    $fileRules[] = 'prohibited';
                }

                if ($field->isMultiple()) {
                    $rules['files.'.$field->name] = [...$base, 'array', 'max:'.$this->maxFiles($field)];
                    $rules['files.'.$field->name.'.*'] = $fileRules;
                } else {
                    $rules['files.'.$field->name] = [...$base, ...$fileRules];
                }

                continue;
            }

            $key = 'values.'.$field->name;

            if ($field->isProfile()) {
                $rules[$key] = [...$base, 'string', 'max:255', ...$this->formatRules($field->format())];
                continue;
            }

            switch ($field->type) {
                case 'text':
                    $rules[$key] = [...$base, 'string', 'max:'.min((int) $field->setting('max_length', 255), 1000), ...$this->formatRules($field->format())];
                    if ($min = (int) $field->setting('min_length')) {
                        $rules[$key][] = 'min:'.$min;
                    }
                    break;

                case 'textarea':
                    $rules[$key] = [...$base, 'string', 'max:'.min((int) $field->setting('max_length', 5000), 20000)];
                    if ($min = (int) $field->setting('min_length')) {
                        $rules[$key][] = 'min:'.$min;
                    }
                    break;

                case 'number':
                    $rules[$key] = [...$base, $field->setting('integer') ? 'integer' : 'numeric'];
                    if (is_numeric($field->setting('min'))) {
                        $rules[$key][] = 'min:'.$field->setting('min');
                    }
                    if (is_numeric($field->setting('max'))) {
                        $rules[$key][] = 'max:'.$field->setting('max');
                    }
                    break;

                case 'select':
                case 'radio':
                case 'checkbox':
                    if ($field->isSingleCheckbox()) {
                        $rules[$key] = $field->is_required ? ['accepted'] : ['nullable', 'boolean'];
                    } elseif ($field->isMultiple()) {
                        $rules[$key] = [...$base, 'array'];
                        $rules[$key.'.*'] = [Rule::in($field->optionList())];
                    } else {
                        $rules[$key] = [...$base, Rule::in($field->optionList())];
                    }
                    break;
            }
        }

        return $rules;
    }

    public function attributes(Form $form): array
    {
        $attributes = [];
        foreach ($form->fields as $field) {
            $prefix = $field->type === 'file' ? 'files.' : 'values.';
            $attributes[$prefix.$field->name] = $field->label;
            $attributes[$prefix.$field->name.'.*'] = $field->label;
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute الزامی است.',
            'accepted' => 'تأیید «:attribute» الزامی است.',
            'string' => ':attribute معتبر نیست.',
            'numeric' => ':attribute باید عدد باشد.',
            'integer' => ':attribute باید عدد صحیح باشد.',
            'min.string' => ':attribute باید حداقل :min کاراکتر باشد.',
            'max.string' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'min.numeric' => ':attribute نباید کمتر از :min باشد.',
            'max.numeric' => ':attribute نباید بیشتر از :max باشد.',
            'max.array' => 'حداکثر :max فایل برای «:attribute» مجاز است.',
            'max.file' => 'حجم هر فایل «:attribute» نباید بیشتر از :max کیلوبایت باشد.',
            'in' => 'گزینه انتخاب‌شده برای «:attribute» معتبر نیست.',
            'array' => ':attribute معتبر نیست.',
            'file' => 'فایل «:attribute» معتبر نیست.',
            'uploaded' => 'آپلود «:attribute» ناموفق بود؛ احتمالا حجم فایل زیاد است.',
            'mimes' => 'نوع فایل «:attribute» مجاز نیست. پسوندهای مجاز: :values',
            'extensions' => 'نوع فایل «:attribute» مجاز نیست. پسوندهای مجاز: :values',
            'prohibited' => 'آپلود فایل برای «:attribute» تنظیم نشده است.',
            'email' => 'فرمت :attribute صحیح نیست.',
            'url' => 'آدرس :attribute صحیح نیست.',
            'regex' => 'فرمت :attribute صحیح نیست.',
            'boolean' => ':attribute معتبر نیست.',
        ];
    }

    /**
     * اعتبارسنجی و ذخیره پاسخ
     *
     * @param  array<string, mixed>  $values  name => value
     * @param  array<string, UploadedFile|UploadedFile[]|null>  $files  name => file(s)
     *
     * @throws ValidationException
     */
    public function submit(Form $form, array $values, array $files, ?Authenticatable $user, ?string $ip = null, ?string $userAgent = null): FormSubmission
    {
        $form->loadMissing('fields');

        if ($reason = $this->denialReason($form, $user)) {
            throw ValidationException::withMessages(['form' => $reason]);
        }

        $values = $this->normalize($form, $values, $user);
        $files = array_filter($files, fn ($f) => $f !== null && $f !== []);

        $validator = Validator::make(['values' => $values, 'files' => $files], $this->rules($form), $this->messages(), $this->attributes($form));

        // فیلد پروفایل قفل که در پایگاه داده خالی است: پیام راهنما به جای «الزامی است»
        $validator->after(function ($v) use ($form, $values) {
            foreach ($form->fields as $field) {
                if ($field->isLocked() && $field->is_required && ($values[$field->name] ?? '') === '') {
                    $v->errors()->forget('values.'.$field->name);
                    $v->errors()->add('values.'.$field->name, '«'.$field->label.'» در پروفایل شما ثبت نشده است. ابتدا پروفایل خود را کامل کنید.');
                }
            }
        });

        $validator->validate();

        $stored = [];

        try {
            return DB::transaction(function () use ($form, $values, $files, $user, $ip, $userAgent, &$stored) {
                $data = [];
                $labels = [];

                foreach ($form->fields as $field) {
                    $labels[$field->name] = $field->label;

                    if ($field->type === 'file') {
                        $list = $files[$field->name] ?? [];
                        $list = is_array($list) ? $list : [$list];
                        $data[$field->name] = [];

                        foreach ($list as $file) {
                            $path = $file->store(trim(config('form-builder.storage.path'), '/').'/'.$form->id.'/'.now()->format('Y-m'), config('form-builder.storage.disk'));
                            $stored[] = $path;
                            $data[$field->name][] = [
                                'path' => $path,
                                'name' => Str::limit(basename($file->getClientOriginalName()), 150, ''),
                                'size' => $file->getSize(),
                                'mime' => $file->getMimeType(),
                            ];
                        }

                        continue;
                    }

                    $data[$field->name] = $values[$field->name] ?? null;
                }

                return $form->submissions()->create([
                    'user_id' => $user?->getAuthIdentifier(),
                    'data' => $data,
                    'labels' => $labels,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk(config('form-builder.storage.disk'))->delete($stored);
            throw $e;
        }
    }

    // پاک‌سازی مقادیر: فقط فیلدهای فرم، trim، ارقام فارسی به انگلیسی و مقدار قفل از پایگاه داده
    private function normalize(Form $form, array $input, ?Authenticatable $user): array
    {
        $values = [];

        foreach ($form->fields as $field) {
            if ($field->type === 'file') {
                continue;
            }

            if ($field->isLocked()) {
                // مقدار ارسالی کاربر نادیده گرفته می‌شود
                $values[$field->name] = ProfileFields::value($field->type, $user);
                continue;
            }

            $value = $input[$field->name] ?? null;

            if ($field->isSingleCheckbox()) {
                $values[$field->name] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                continue;
            }

            if (is_array($value)) {
                $value = array_values(array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : null, $value), fn ($v) => $v !== null && $v !== ''));
                $values[$field->name] = $field->isMultiple() ? $value : ($value[0] ?? null);
                continue;
            }

            $value = is_scalar($value) ? trim((string) $value) : null;

            if ($value !== null && ($field->inputType() === 'number' || $field->inputType() === 'tel' || in_array($field->format(), ['mobile', 'national_code', 'postal_code', 'digits'], true))) {
                $value = FieldTypes::toEnglishDigits($value);
            }

            $values[$field->name] = $value === '' ? null : ($field->isMultiple() ? [$value] : $value);
        }

        return $values;
    }

    private function formatRules(?string $format): array
    {
        return match ($format) {
            'email' => ['email'],
            'mobile' => ['regex:/^09\d{9}$/'],
            'national_code' => [new NationalCode],
            'postal_code' => ['regex:/^\d{10}$/'],
            'digits' => ['regex:/^\d+$/'],
            'persian' => ['regex:/^[\x{0600}-\x{06FF}\x{200C}\s]+$/u'],
            'latin' => ['regex:/^[A-Za-z0-9\s.\-_]+$/'],
            'url' => ['url'],
            default => [],
        };
    }

    private function fileMaxKb(FormField $field): int
    {
        return max(1, min((int) $field->setting('max_size_kb', 2048), (int) config('form-builder.uploads.max_size_kb', 20480)));
    }

    private function maxFiles(FormField $field): int
    {
        return max(1, min((int) $field->setting('max_files', 3), (int) config('form-builder.uploads.max_files', 10)));
    }

    // نام‌های mime معادل پسوندها برای قاعده mimes (mp3 در لاراول mpga شناخته می‌شود)
    private function mimesFor(array $extensions): array
    {
        $extra = ['mp3' => ['mpga', 'mp3'], 'jpg' => ['jpg', 'jpeg'], 'mov' => ['mov', 'qt'], 'txt' => ['txt']];
        $list = [];
        foreach ($extensions as $ext) {
            array_push($list, ...($extra[$ext] ?? [$ext]));
        }

        return array_values(array_unique($list));
    }
}
