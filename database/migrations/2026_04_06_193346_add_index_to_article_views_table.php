<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('article_views', function (Blueprint $table) {
            $table->index(['article_id', 'date_hour'], 'article_views_article_id_date_hour_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_views', function (Blueprint $table) {
            $table->dropIndex('article_views_article_id_date_hour_index');
        });
    }
};
