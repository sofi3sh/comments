<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('articles_block_settings')) {
            return;
        }

        Schema::create('articles_block_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();

            $table->timestamp('published_at_from')->nullable();
            $table->timestamp('published_at_to')->nullable();

            $table->json('marker_ids')->nullable();
            $table->json('tag_ids')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique('site_id', 'articles_block_settings_site_id_unique');

            $table->foreign('category_id', 'articles_block_settings_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();

            $table->foreign('site_id', 'articles_block_settings_site_id_foreign')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();

            $table->foreign('type_id', 'articles_block_settings_type_id_foreign')
                ->references('id')
                ->on('article_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles_block_settings');
    }
};

