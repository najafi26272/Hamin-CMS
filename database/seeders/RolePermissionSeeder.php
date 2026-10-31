<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // مجوزها
        $users = Permission::updateOrCreate(
            ['name' => 'manage-users'],
            ['label' => 'دسترسی به کاربران']
        );
        $content = Permission::updateOrCreate(
            ['name' => 'manage-content'],
            ['label' => 'دسترسی به محتوا']
        );

        // نقش‌ها
        $superAdmin = Role::updateOrCreate(
            ['name' => 'admin'],
            ['label' => 'ادمین اصلی']
        );
        $author = Role::updateOrCreate(
            ['name' => 'author'],
            ['label' => 'نویسنده']
        );

        // مجوزهای هر نقش
        $superAdmin->permissions()->sync([$users->id, $content->id]);
        $author->permissions()->sync([$content->id]);
    }
}
