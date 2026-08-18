<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Articles\Marker;
use App\Models\Articles\Translate\MarkerTranslation;
use Illuminate\Support\Facades\DB;

class MarkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $markers = [
            1 => [
                'uk' => 'Актуально',
                'ru' => 'Актуально',
                'en' => 'Actual',
            ],
            2 => [
                'uk' => 'Важливо',
                'ru' => 'Важно',
                'en' => 'Important',
            ],
            3 => [
                'uk' => 'PR',
                'ru' => 'PR',
                'en' => 'PR',
            ],
            4 => [
                'uk' => 'Екскюзив',
                'ru' => 'Эксклюзив',
                'en' => 'Exclusive',
            ],
            5 => [
                'uk' => 'Новини партнерів',
                'ru' => 'Новости партнеров',
                'en' => 'Partner News',
            ],
            6 => [
                'uk' => 'Інсайд',
                'ru' => 'Инсайд',
                'en' => 'Inside',
            ],
        ];

        foreach ($markers as $markerId => $translations) {
            // Check if marker already exists
            $existingMarker = Marker::find($markerId);
            
            if (!$existingMarker) {
                // Create marker with specific ID
                // Since markers table uses auto_increment, we need to temporarily disable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('markers')->insert([
                    'id' => $markerId,
                    'icon' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            // Create or update translations
            foreach (['uk', 'ru', 'en'] as $locale) {
                $translation = MarkerTranslation::where('marker_id', $markerId)
                    ->where('locale', $locale)
                    ->first();

                if ($translation) {
                    // Update existing translation
                    $translation->update([
                        'name' => $translations[$locale] ?? '',
                    ]);
                } else {
                    // Create new translation
                    MarkerTranslation::create([
                        'marker_id' => $markerId,
                        'locale' => $locale,
                        'name' => $translations[$locale] ?? '',
                    ]);
                }
            }
        }
        
        // Update AUTO_INCREMENT to avoid conflicts with future inserts
        $breakingNewsMarker = Marker::query()
            ->where('code', 'breaking_news')
            ->first();

        if (! $breakingNewsMarker) {
            $breakingNewsMarker = new Marker();
            $breakingNewsMarker->forceFill([
                'code' => 'breaking_news',
                'is_system' => true,
                'is_active' => true,
            ]);
            $breakingNewsMarker->save();
        }

        foreach ([
            'uk' => 'Швидка новина',
            'ru' => 'Срочная новость',
            'en' => 'Breaking News',
        ] as $locale => $name) {
            MarkerTranslation::query()->updateOrCreate(
                [
                    'marker_id' => $breakingNewsMarker->id,
                    'locale' => $locale,
                ],
                ['name' => $name],
            );
        }

        $maxId = (int) DB::table('markers')->max('id');
        DB::statement("ALTER TABLE markers AUTO_INCREMENT = " . ($maxId + 1));
    }
}
