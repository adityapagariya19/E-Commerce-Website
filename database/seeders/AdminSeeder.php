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

        User::updateOrCreate(['email' => $email], ['name' => 'admin', 'password' => $password, 'role' => 'super_admin', 'is_active' => true]);

        // Extra demo logins ONLY in local/testing so each role can be tried. Never created in production.
        if (app()->environment(['local', 'testing'])) {
            foreach ([['Demo Store Manager', 'manager@jboptician.test', 'store_manager'], ['Demo Content Manager', 'content@jboptician.test', 'content_manager'], ['Demo Order Support', 'support@jboptician.test', 'order_support']] as [$n, $e, $r]) {
                User::updateOrCreate(['email' => $e], ['name' => $n, 'password' => $password, 'role' => $r, 'is_active' => true]);
            }
            User::updateOrCreate(['email' => 'customer@jboptician.test'], ['name' => 'Demo Customer', 'password' => $password, 'role' => 'customer', 'is_active' => true]);
        }
    }
}
