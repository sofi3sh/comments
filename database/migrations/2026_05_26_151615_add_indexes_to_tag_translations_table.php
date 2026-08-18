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
        Schema::table('tag_translations', function (Blueprint $table) {
            $table->index('title', 'idx_tag_translations_title');
            $table->index('slug', 'idx_tag_translations_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tag_translations', function (Blueprint $table) {
            $table->dropIndex('idx_tag_translations_title');
            $table->dropIndex('idx_tag_translations_slug');
        });
    }
};
