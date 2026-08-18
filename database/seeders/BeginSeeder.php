<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BeginSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ServicePagesSeeder::class,
        ]);
    }
}