<?php

namespace Hamin\FormBuilder\Models;

use Hamin\FormBuilder\Support\ProfileFields;
use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $table = 'form_builder_submissions';

    protected $fillable = ['form_id', 'user_id', 'data', 'labels', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'labels' => 'array',
        ];
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function user()
    {
        return $this->belongsTo(ProfileFields::userModel(), 'user_id');
    }

    // آیا مقدار ذخیره‌شده فایل است؟
    public static function isFileValue(mixed $value): bool
    {
        return is_array($value) && isset($value[0]) && is_array($value[0]) && array_key_exists('path', $value[0]);
    }

    // مقدار قابل نمایش (متن ساده) برای جدول و خروجی CSV
    public function displayValue(string $name): string
    {
        $value = $this->data[$name] ?? null;

        return match (true) {
            $value === null || $value === '' || $value === [] => '',
            self::isFileValue($value) => implode('، ', array_column($value, 'name')),
            is_bool($value) => $value ? 'بله' : 'خیر',
            is_array($value) => implode('، ', $value),
            default => (string) $value,
        };
    }
}
