<?php

namespace Database\Seeders;

use Database\Seeders\ServicePages\AboutPageSeeder;
use Database\Seeders\ServicePages\AccessibilityPageSeeder;
use Database\Seeders\ServicePages\CookiePageSeeder;
use Database\Seeders\ServicePages\NewslettersPageSeeder;
use Database\Seeders\ServicePages\PrivacyPageSeeder;
use Database\Seeders\ServicePages\TermsPageSeeder;
use Illuminate\Database\Seeder;

class ServicePagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TermsPageSeeder::class,
            PrivacyPageSeeder::class,
            AboutPageSeeder::class,
            CookiePageSeeder::class,
            AccessibilityPageSeeder::class,
            NewslettersPageSeeder::class,
        ]);
    }
}