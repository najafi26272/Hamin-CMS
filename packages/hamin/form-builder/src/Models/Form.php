<?php

namespace Hamin\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Form extends Model
{
    use SoftDeletes;

    protected $table = 'form_builder_forms';

    protected $fillable = [
        'title', 'slug', 'description', 'is_active', 'requires_auth', 'allow_multiple', 'submit_label', 'success_message',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_auth' => 'boolean',
            'allow_multiple' => 'boolean',
        ];
    }

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    // فرمی که فیلد پروفایل دارد بدون ورود کاربر قابل تکمیل نیست
    public function needsLogin(): bool
    {
        return $this->requires_auth || $this->fields->contains(fn (FormField $f) => $f->isProfile());
    }

    public function hasFileFields(): bool
    {
        return $this->fields->contains(fn (FormField $f) => $f->type === 'file');
    }

    public function getSubmitTextAttribute(): string
    {
        return $this->submit_label ?: 'ثبت فرم';
    }

    public function getSuccessTextAttribute(): string
    {
        return $this->success_message ?: 'فرم با موفقیت ثبت شد. سپاس از شما.';
    }

    /**
     * slug قابل استفاده در آدرس: فاصله‌ها به خط تیره، حروف فارسی و انگلیسی حفظ می‌شوند
     */
    public static function makeSlug(string $text, ?int $ignoreId = null): string
    {
        $text = str_replace(['ي', 'ك', "\u{200C}"], ['ی', 'ک', ''], trim($text));
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($text));
        $slug = trim($slug, '-') ?: Str::lower(Str::random(6));

        $base = Str::limit($slug, 80, '');
        $slug = $base;
        $i = 2;
        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
