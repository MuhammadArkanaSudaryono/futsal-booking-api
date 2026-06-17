<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'adminfutsalbooking@gmail.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('admin123'),
                'phone'     => '081200000001',
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        // Sample user pelanggan
        User::updateOrCreate(
            ['email' => 'userfutsalbooking@gmail.com'],
            [
                'name'      => 'Budi Santoso',
                'password'  => Hash::make('user123'),
                'phone'     => '081200000002',
                'role'      => 'user',
                'is_active' => true,
            ]
        );
    }
}
