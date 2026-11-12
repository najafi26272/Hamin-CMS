<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'pending' => 'در انتظار تأیید',
        'approved' => 'تأیید شده',
        'spam' => 'اسپم',
    ];

    protected $fillable = [
        'post_id', 'parent_id', 'user_id', 'name', 'contact', 'body', 'status', 'ip_address', 'user_agent',
    ];

    protected static function booted(): void
    {
        // با حذف یک دیدگاه، پاسخ‌های آن هم حذف می‌شوند
        static::deleting(function (Comment $comment) {
            $comment->replies()->get()->each->delete();
        });
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // پاسخی که از پنل مدیریت ثبت شده است
    public function isAdminReply(): bool
    {
        return $this->user_id !== null;
    }
}
