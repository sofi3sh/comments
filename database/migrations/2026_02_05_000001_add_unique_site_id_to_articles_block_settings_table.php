<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'articles_block_settings';
    private const UNIQUE_INDEX_NAME = 'articles_block_settings_site_id_unique';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $indexExists = DB::select(
            "SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?",
            [self::TABLE_NAME, self::UNIQUE_INDEX_NAME]
        );

        if (!empty($indexExists)) {
            return;
        }

        Schema::table(self::TABLE_NAME, function (Blueprint $table) {
            $table->unique('site_id', self::UNIQUE_INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $indexExists = DB::select(
            "SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?",
            [self::TABLE_NAME, self::UNIQUE_INDEX_NAME]
        );

        if (empty($indexExists)) {
            return;
        }

        Schema::table(self::TABLE_NAME, function (Blueprint $table) {
            $table->dropUnique(self::UNIQUE_INDEX_NAME);
        });
    }
};

