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
            // Remove global unique constraint on slug
            $table->dropUnique(['slug']);
            
            // Add unique constraint on slug + locale combination
            $table->unique(['slug', 'locale'], 'unique_tag_translation_slug_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tag_translations', function (Blueprint $table) {
            // Remove unique constraint on slug + locale
            $table->dropUnique('unique_tag_translation_slug_locale');
            
            // Restore global unique constraint on slug
            $table->unique('slug');
        });
    }
};
