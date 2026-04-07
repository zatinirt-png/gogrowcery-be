<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gogrowcery.com'],
            [
                'name'      => 'Super Admin',
                'password'  => 'admin123456',
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
    }
}
