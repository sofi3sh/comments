<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sitemap generation filters every query by status plus a published_at range:
 * the per-locale files exclude future dates, and the Google News files select
 * a rolling window. Neither column was indexed.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'articles_status_published_at_index';

    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->index(['status', 'published_at'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
        });
    }
};
