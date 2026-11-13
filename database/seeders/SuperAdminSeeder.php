<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => UserRole::ADMIN,
            ]
        );
    }
}
