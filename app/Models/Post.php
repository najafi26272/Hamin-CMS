<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasSlug, SoftDeletes;

    public const STATUSES = [
        'draft' => 'پیش‌نویس',
        'pending' => 'در انتظار بررسی',
        'published' => 'منتشر شده',
        'rejected' => 'رد شده',
    ];

    /**
     * دسته‌بندی‌های ثابت و اصلی مطالب (نوع محتوا) و فیلدهای هر کدام
     * creator: برچسب فیلد نام صاحب اثر (null یعنی این فیلد را ندارد)
     * media:   نوع فایل اصلی اجباری (image / audio / video یا null)
     */
    public const TYPES = [
        'news' => [
            'label' => 'اخبار', 'icon' => 'file-text', 'creator' => null, 'media' => null,
            'content_label' => 'متن خبر', 'content_required' => true, 'poster_label' => 'تصویر شاخص',
        ],
        'visual' => [
            'label' => 'لوح', 'icon' => 'image', 'creator' => 'نام صاحب اثر', 'media' => 'image', 'media_label' => 'فایل اصلی (عکس یا پوستر)',
            'content_label' => 'توضیحات', 'content_required' => false, 'poster_label' => 'پوستر نمایشی برای لیست محتوا',
        ],
        'audio' => [
            'label' => 'شنیداری', 'icon' => 'music', 'creator' => 'نام صاحب اثر', 'media' => 'audio', 'media_label' => 'فایل صوتی',
            'content_label' => 'توضیحات یا متن شعر', 'content_required' => false, 'poster_label' => 'فایل پوستر',
        ],
        'video' => [
            'label' => 'تماشایی', 'icon' => 'film', 'creator' => 'نام سازنده', 'media' => 'video', 'media_label' => 'فایل ویدیو',
            'content_label' => 'توضیحات', 'content_required' => false, 'poster_label' => 'فایل پوستر',
        ],
        'poem' => [
            'label' => 'شعر', 'icon' => 'feather', 'creator' => 'نام شاعر', 'media' => null,
            'content_label' => 'متن شعر', 'content_required' => true, 'poster_label' => 'فایل پوستر',
        ],
    ];

    protected $fillable = [
        'user_id', 'type', 'title', 'slug', 'creator_name', 'content', 'image', 'media_file', 'status', 'published_at',
        'rejection_reason', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ---------- بررسی مطلب ----------

    // مطلب در انتظار بررسی یا رد شده را می‌توان تأیید کرد
    public function canBeApproved(): bool
    {
        return in_array($this->status, ['pending', 'rejected']);
    }

    // فقط مطلب در انتظار بررسی رد می‌شود
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(User $reviewer): void
    {
        $this->update([
            'status' => 'published',
            // اگر زمان انتشار آینده تعیین شده باشد، حفظ می‌شود
            'published_at' => $this->published_at?->isFuture() ? $this->published_at : now(),
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function reject(User $reviewer, string $reason = ''): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => mb_substr(trim($reason), 0, 1000) ?: null,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ]);
    }

    // تنظیمات نوع محتوای این مطلب (از TYPES)
    public function typeConfig(): array
    {
        return self::TYPES[$this->type] ?? self::TYPES['news'];
    }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_file ? Storage::disk('public')->url($this->media_file) : null;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments()
    {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    public function pageViews()
    {
        return $this->morphMany(PageView::class, 'viewable');
    }

    // مطالب قابل نمایش در سایت: منتشرشده و زمان انتشارشان رسیده
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    // خلاصه متن بدون HTML (برای کارت‌ها و توضیحات صفحه)
    public function excerpt(int $length = 140): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '<br>'], ' ', (string) $this->content)))));

        return \Illuminate\Support\Str::limit($text, $length);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    // تاریخ انتشار به شمسی و به وقت تهران، مثل: 1405/08/09 14:30
    public function getPublishedAtJalaliAttribute(): ?string
    {
        return $this->published_at
            ? jdate($this->published_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d H:i')
            : null;
    }
}
