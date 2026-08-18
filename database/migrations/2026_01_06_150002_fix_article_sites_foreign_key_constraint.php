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
        Schema::table('article_sites', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['article_id']);
            
            // Recreate with cascadeOnDelete instead of cascadeSetNull
            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_sites', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['article_id']);
            
            // Recreate with cascadeSetNull (original)
            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->cascadeSetNull();
        });
    }
};

