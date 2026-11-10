<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_SEED_EMAIL', 'admin@gmail.com');
        $password = env('ADMIN_SEED_PASSWORD', 'admin');

        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'admin', 'password' => $password, 'role' => 'super_admin', 'is_active' => true]
        );
    }
}
