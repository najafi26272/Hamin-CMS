<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasSlug, SoftDeletes;

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'video_path', 'thumbnail'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getVideoUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->video_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null;
    }

    // حجم فایل ویدیو به مگابایت
    public function getSizeMbAttribute(): ?float
    {
        $disk = Storage::disk('public');

        return $disk->exists($this->video_path) ? round($disk->size($this->video_path) / 1048576, 1) : null;
    }
}
