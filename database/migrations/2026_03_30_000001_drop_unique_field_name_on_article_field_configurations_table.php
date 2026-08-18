<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'article_field_configurations';
    private const UNIQUE_INDEX_NAME = 'article_field_configurations_field_name_unique';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $rows = DB::select(
            "SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1",
            [self::TABLE_NAME, self::UNIQUE_INDEX_NAME]
        );

        if (empty($rows)) {
            return;
        }

        Schema::table(self::TABLE_NAME, function (Blueprint $table): void {
            $table->dropUnique(self::UNIQUE_INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $rows = DB::select(
            "SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1",
            [self::TABLE_NAME, self::UNIQUE_INDEX_NAME]
        );

        if (! empty($rows)) {
            return;
        }

        Schema::table(self::TABLE_NAME, function (Blueprint $table): void {
            $table->unique('field_name', self::UNIQUE_INDEX_NAME);
        });
    }
};

