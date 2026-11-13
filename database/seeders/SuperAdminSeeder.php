<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Super Admin',
                'username' => 'admin',
                'password' => 'admin123', // Model will hash this via 'hashed' cast
                'role' => UserRole::ADMIN,
            ]
        );
    }
}
