<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@gogrowcery.com',
                'password' => 'admin123456',
                'role' => 'admin',
                'is_active' => true,
            ],
        );
    }
}
