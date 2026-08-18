<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'articles_block_settings';

    /** @var array<string, string> */
    private const KEY_RENAMES = [
        'top-materials' => 'main-container-right',
        'ai-selected-news' => 'articles-container-left',
        'ai-latest-news' => 'main-container-left',
        'journalists-bloggers-latest' => 'swiper-container',
        'swiper-block' => 'articles-container-right',
    ];

    public function up(): void
    {
        $this->renameKeys(self::KEY_RENAMES);
    }

    public function down(): void
    {
        $this->renameKeys(array_flip(self::KEY_RENAMES));
    }

    /**
     * @param array<string, string> $renames old key => new key
     */
    private function renameKeys(array $renames): void
    {
        if (
            ! Schema::hasTable(self::TABLE_NAME)
            || ! Schema::hasColumn(self::TABLE_NAME, 'id')
            || ! Schema::hasColumn(self::TABLE_NAME, 'site_id')
            || ! Schema::hasColumn(self::TABLE_NAME, 'block_key')
        ) {
            return;
        }

        foreach ($renames as $oldKey => $newKey) {
            $settings = DB::table(self::TABLE_NAME)
                ->select(['id', 'site_id'])
                ->where('block_key', $oldKey)
                ->get();

            foreach ($settings as $setting) {
                $targetExists = DB::table(self::TABLE_NAME)
                    ->where('site_id', $setting->site_id)
                    ->where('block_key', $newKey)
                    ->exists();

                if (! $targetExists) {
                    DB::table(self::TABLE_NAME)
                        ->where('id', $setting->id)
                        ->update(['block_key' => $newKey]);
                }
            }
        }
    }
};
