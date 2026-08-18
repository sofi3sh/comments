<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'articles_block_settings';
    private const OLD_UNIQUE_INDEX_NAME = 'articles_block_settings_site_id_unique';
    private const NEW_UNIQUE_INDEX_NAME = 'articles_block_settings_site_id_block_key_unique';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            $this->createTable();

            return;
        }

        $this->addMissingColumns();
        $this->ensureUniqueIndexes();
    }

    private function createTable(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('site_id');

            $table->string('block_key', 100)->default('default');

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('limit')->default(8);

            $table->string('order_by', 30)->default('views');
            $table->string('order_direction', 4)->default('desc');

            $table->unsignedInteger('views_window_hours')->nullable();

            $table->unsignedInteger('refresh_interval_hours')->default(4);

            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();

            $table->json('author_role_ids')->nullable();

            $table->json('marker_ids')->nullable();
            $table->json('tag_ids')->nullable();

            $table->timestamp('published_at_from')->nullable();
            $table->timestamp('published_at_to')->nullable();

            $table->timestamp('updated_at_from')->nullable();
            $table->timestamp('updated_at_to')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['site_id', 'block_key'], self::NEW_UNIQUE_INDEX_NAME);

            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();

            $table->foreign('type_id')
                ->references('id')
                ->on('article_types')
                ->nullOnDelete();

            $table->index('category_id');
            $table->index('type_id');
        });
    }

    private function addMissingColumns(): void
    {
        Schema::table(self::TABLE_NAME, function (Blueprint $table): void {
            if (!Schema::hasColumn(self::TABLE_NAME, 'block_key')) {
                $table->string('block_key', 100)->default('default');
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'limit')) {
                $table->unsignedInteger('limit')->default(8);
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'order_by')) {
                $table->string('order_by', 30)->default('views');
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'order_direction')) {
                $table->string('order_direction', 4)->default('desc');
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'views_window_hours')) {
                $table->unsignedInteger('views_window_hours')->nullable();
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'refresh_interval_hours')) {
                $table->unsignedInteger('refresh_interval_hours')->default(4);
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'author_role_ids')) {
                $table->json('author_role_ids')->nullable();
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'updated_at_from')) {
                $table->timestamp('updated_at_from')->nullable();
            }

            if (!Schema::hasColumn(self::TABLE_NAME, 'updated_at_to')) {
                $table->timestamp('updated_at_to')->nullable();
            }
        });

        DB::table(self::TABLE_NAME)
            ->whereNull('block_key')
            ->update(['block_key' => 'default']);
    }

    private function ensureUniqueIndexes(): void
    {
        if ($this->indexExists(self::OLD_UNIQUE_INDEX_NAME)) {
            $this->dropSiteForeignKeyIfExists();

            Schema::table(self::TABLE_NAME, function (Blueprint $table): void {
                $table->dropUnique(self::OLD_UNIQUE_INDEX_NAME);
            });
        }

        if (! $this->indexExists(self::NEW_UNIQUE_INDEX_NAME)) {
            Schema::table(self::TABLE_NAME, function (Blueprint $table): void {
                $table->unique(['site_id', 'block_key'], self::NEW_UNIQUE_INDEX_NAME);
            });
        }

        $this->ensureSiteForeignKeyExists();
    }

    private function indexExists(string $indexName): bool
    {
        $rows = DB::select(
            "SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1",
            [self::TABLE_NAME, $indexName]
        );

        return !empty($rows);
    }

    private function getSiteForeignKeyConstraintName(): ?string
    {
        $rows = DB::select(
            "SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = 'site_id'
              AND REFERENCED_TABLE_NAME = 'sites'
            LIMIT 1",
            [self::TABLE_NAME]
        );

        return $rows[0]->CONSTRAINT_NAME ?? null;
    }

    private function dropSiteForeignKeyIfExists(): void
    {
        $constraintName = $this->getSiteForeignKeyConstraintName();

        if ($constraintName === null) {
            return;
        }

        DB::statement("ALTER TABLE `" . self::TABLE_NAME . "` DROP FOREIGN KEY `" . $constraintName . "`");
    }

    private function ensureSiteForeignKeyExists(): void
    {
        $constraintName = $this->getSiteForeignKeyConstraintName();

        if ($constraintName !== null) {
            return;
        }

        Schema::table(self::TABLE_NAME, function (Blueprint $table): void {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally no rollback logic: this table is configuration/stateful,
        // and dropping columns/constraints during rollback is risky.
    }

};

