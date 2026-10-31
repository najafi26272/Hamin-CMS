<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'first_name' => 'مدیر',
                'last_name'  => 'تستی',
                'birth_date' => '1990-01-01',
                'mobile'     => '09120000000',
                'phone'      => '02100000000',
                'email'      => 'admin@example.com',
                'city'       => 'تهران',
                'password'   => Hash::make('123456'),
                'email_verified_at' => now(),
            ]
        );

        $role = Role::where('name', 'admin')->first();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
