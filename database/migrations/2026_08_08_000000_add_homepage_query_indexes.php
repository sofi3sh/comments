<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATEGORY_INDEX = 'homepage_articles_category_status_published_index';
    private const TYPE_INDEX = 'homepage_articles_type_status_published_index';
    private const VIEW_PERIOD_INDEX = 'article_views_date_hour_article_id_index';

    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Індекси відповідають фільтрам блоку: категорія/тип + статус + дата.
            if (! $this->hasIndexForColumns('articles', ['category_id', 'status', 'published_at'])) {
                $table->index(['category_id', 'status', 'published_at'], self::CATEGORY_INDEX);
            }

            if (! $this->hasIndexForColumns('articles', ['type_id', 'status', 'published_at'])) {
                $table->index(['type_id', 'status', 'published_at'], self::TYPE_INDEX);
            }
        });

        Schema::table('article_views', function (Blueprint $table) {
            // Рейтинг починається з часового вікна переглядів, а не з усіх статей.
            $table->index(['date_hour', 'article_id'], self::VIEW_PERIOD_INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('article_views', function (Blueprint $table) {
            $table->dropIndex(self::VIEW_PERIOD_INDEX);
        });

        Schema::table('articles', function (Blueprint $table) {
            // Видаляємо лише індекси, які належать цій міграції.
            if ($this->hasIndexByName('articles', self::TYPE_INDEX)) {
                $table->dropIndex(self::TYPE_INDEX);
            }

            if ($this->hasIndexByName('articles', self::CATEGORY_INDEX)) {
                $table->dropIndex(self::CATEGORY_INDEX);
            }
        });
    }

    /**
     * Визначає еквівалентний індекс за порядком колонок, незалежно від його назви.
     */
    private function hasIndexForColumns(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $index): bool => array_values($index['columns'] ?? []) === $columns
        );
    }

    /**
     * Перевіряє наявність індексу, створеного саме цією міграцією.
     */
    private function hasIndexByName(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->contains('name', $name);
    }
};
