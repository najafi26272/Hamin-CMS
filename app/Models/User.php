<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    // گزینه‌های مدرک تحصیلی
    public const EDUCATION_DEGREES = [
        'زیر دیپلم',
        'دیپلم',
        'کاردانی',
        'کارشناسی',
        'کارشناسی ارشد',
        'دکتری',
    ];

    // وضعیت درخواست هنرمندی (null یعنی کاربر عادی که درخواستی نداده است)
    public const ARTIST_STATUSES = [
        'pending' => 'در انتظار بررسی',
        'approved' => 'هنرمند تأییدشده',
        'rejected' => 'رد شده',
    ];

    protected $fillable = [
        'first_name', 'last_name', 'birth_date', 'avatar', 'phone', 'mobile',
        'email', 'username', 'city', 'virtual_space_number', 'instagram_id',
        'education_degree', 'password', 'artist_status', 'artist_rejection_reason', 'reviewed_at', 'reviewed_by','mobile_verified_at'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'email_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'password' => 'hashed',
            'mobile_verified_at' => 'datetime'
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function artSpecialties()
    {
        return $this->belongsToMany(ArtSpecialty::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // آیا درخواست هنرمندی کاربر در انتظار بررسی است؟
    public function isArtistPending(): bool
    {
        return $this->artist_status === 'pending';
    }

    public function isArtist(): bool
    {
        return $this->roles->contains('name', 'artist');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * دسته‌بندی‌هایی (نوع محتوا) که کاربر در فرم ثبت مطلب می‌بیند:
     * - «اخبار» فقط با مجوز ثبت خبر
     * - مدیر و نویسنده (مدیریت محتوا): همه دسته‌بندی‌های دیگر
     * - هنرمند: فقط دسته‌بندی‌های مربوط به تخصص‌های خودش
     */
    public function allowedPostTypes(): array
    {
        $gate = Gate::forUser($this);
        $types = [];

        if ($gate->allows('publish-news')) {
            $types[] = 'news';
        }

        if ($gate->allows('manage-content')) {
            $types = array_merge($types, ['visual', 'audio', 'video', 'poem']);
        } else {
            $this->loadMissing('artSpecialties');
            $types = array_merge($types, $this->artSpecialties->pluck('content_type')->filter()->all());
        }

        // به ترتیب TYPES
        return array_values(array_intersect(array_keys(Post::TYPES), $types));
    }

    // کاربرانی که مطلب را مستقیم منتشر می‌کنند (بقیه مطالبشان باید بررسی شود)
    public function canPublishDirectly(): bool
    {
        return Gate::forUser($this)->any(['manage-content', 'edit-content']);
    }

    // آیا کاربر نقش مدیر اصلی را دارد؟
    public function isAdmin(): bool
    {
        return $this->roles->contains('name', 'admin');
    }

    // شماره موبایل به شکل استاندارد 09xxxxxxxxx: ارقام فارسی/عربی، فاصله، خط تیره و پیشوند +98 / 0098 / 98
    public static function normalizeMobile(string $mobile): string
    {
        $mobile = strtr($mobile, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $mobile = preg_replace('/[\s\-()]+/', '', $mobile);
        $mobile = preg_replace('/^(?:\+98|0098|98)(?=9\d{9}$)/', '0', $mobile);

        return preg_match('/^9\d{9}$/', $mobile) ? '0'.$mobile : $mobile;
    }

    // در حالت توسعه سایت فقط این کاربران (مدیر اصلی و نویسندگان) وارد می‌شوند و سایت را می‌بینند
    public function canBypassMaintenance(): bool
    {
        return $this->roles->contains(fn ($role) => in_array($role->name, Setting::MAINTENANCE_ROLES, true));
    }

    // آیا یکی از نقش‌های کاربر این مجوز را دارد؟
    public function hasPermission(string $name): bool
    {
        $this->loadMissing('roles.permissions');

        return $this->roles->contains(fn ($role) => $role->permissions->contains('name', $name));
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? Storage::disk('public')->url($this->avatar)
            : asset('assets/admin/images/faces/pro.png');
    }
}
