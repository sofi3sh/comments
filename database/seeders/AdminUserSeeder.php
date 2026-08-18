<?php

namespace Database\Seeders;

use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::updateOrCreate(
            [
                'name' => 'Admin',
                'guard_name' => 'web',
            ],
            [
                'rank' => 10,
            ]
        );

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin@admin.com'),
                'email_verified_at' => now(),
            ]
        );

        if (! $adminUser->hasVerifiedEmail()) {
            $adminUser->markEmailAsVerified();
        }

        if (! $adminUser->hasRole($adminRole)) {
            $adminUser->assignRole($adminRole);
        }
    }
}
