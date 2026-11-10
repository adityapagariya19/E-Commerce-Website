<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            AdminSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
            CouponSeeder::class,
            DemoOrderSeeder::class, // DEMO data only — flagged orders.is_demo = 1
        ]);
    }
}
