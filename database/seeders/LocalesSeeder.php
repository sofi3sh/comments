<?php

namespace Database\Seeders;

use App\Models\Settings\Locale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class LocalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('locales');

        $this->copyFilesToStorage();

        $locales = [
            [
                'code' => 'uk',
                'name' => 'Ukrainian',
                'prefix' => 'uk',
                'icon' => 'locales/uk.png',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ru',
                'name' => 'Russian',
                'prefix' => 'ru',
                'icon' => 'locales/ru.png',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'prefix' => 'en',
                'icon' => 'locales/en.png',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($locales as $localeData) {
            Locale::updateOrCreate(
                ['code' => $localeData['code']],
                $localeData
            );
        }
    }

    /**
    * Copy files from resources/seeders/locales/ to storage/app/public/locales/
    * If the files already exist, they will not be overwritten
     * @return void
     */
    private function copyFilesToStorage(): void
    {
        $files = ['uk.png', 'ru.png', 'en.png'];

        foreach ($files as $file) {
            $sourcePath = resource_path('seeders/locales/' . $file);
            $destinationPath = 'locales/' . $file;

            if (!file_exists($sourcePath)) {
                continue;
            }

            if (!Storage::disk('public')->exists($destinationPath)) {
                Storage::disk('public')->put(
                    $destinationPath,
                    file_get_contents($sourcePath)
                );
            }
        }
    }
}
