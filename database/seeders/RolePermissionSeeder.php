<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // مجوزها از config/permissions.php خوانده می‌شوند
        foreach (config('permissions') as $name => $permission) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['label' => $permission['label'], 'description' => $permission['description'] ?? null]
            );
        }

        // مجوزهایی که دیگر در کد تعریف نشده‌اند حذف شوند
        Permission::whereNotIn('name', array_keys(config('permissions')))->delete();

        // نقش‌ها
        $superAdmin = Role::updateOrCreate(
            ['name' => 'admin'],
            ['label' => 'ادمین اصلی']
        );
        $author = Role::firstOrCreate(
            ['name' => 'author'],
            ['label' => 'نویسنده']
        );

        // هنرمندانی که از سایت ثبت‌نام می‌کنند (فقط ارسال مطلب در دسته‌بندی‌های مربوط به تخصص خود)
        $artist = Role::firstOrCreate(
            ['name' => 'artist'],
            ['label' => 'هنرمند', 'description' => 'هنرمندان ثبت‌نام شده از سایت']
        );

        // مدیر اصلی همه مجوزها را دارد
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // نویسنده به صورت پیش‌فرض به محتوا دسترسی دارد (بقیه از پنل قابل تغییر است)
        $author->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', ['manage-content', 'edit-content', 'publish-news'])->pluck('id')
        );

        $artist->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', ['submit-content'])->pluck('id')
        );
    }
}
