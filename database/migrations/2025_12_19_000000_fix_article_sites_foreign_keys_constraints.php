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
            // Drop existing foreign keys
            $table->dropForeign(['article_id']);
            $table->dropForeign(['site_id']);
        });

        Schema::table('article_sites', function (Blueprint $table) {
            // Recreate foreign keys with cascadeOnDelete instead of cascadeSetNull
            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade');
            
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_sites', function (Blueprint $table) {
            // Drop foreign keys with cascadeOnDelete
            $table->dropForeign(['article_id']);
            $table->dropForeign(['site_id']);
        });

        Schema::table('article_sites', function (Blueprint $table) {
            // Restore original foreign keys with cascadeSetNull
            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->onDelete('set null');
            
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->onDelete('set null');
        });
    }
};

