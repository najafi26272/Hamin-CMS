<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    protected $fillable = ['title', 'image', 'link', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // با حذف اسلایدر، فایل بنر هم پاک می‌شود
        static::deleted(fn (Slider $slider) => Storage::disk('public')->delete($slider->image));
    }

    // اسلایدرهای فعال به ترتیب نمایش (برای صفحه اول سایت)
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderByDesc('id');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image);
    }
}
