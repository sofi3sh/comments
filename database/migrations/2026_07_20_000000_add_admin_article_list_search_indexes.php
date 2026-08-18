<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->index('name', 'idx_sites_name');
        });

        Schema::table('category_translations', function (Blueprint $table) {
            $table->index('name', 'idx_category_translations_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('name', 'idx_users_name');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->index('homepage', 'idx_tags_homepage');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->index('filename', 'idx_attachments_filename');
            $table->index('alt', 'idx_attachments_alt');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex('idx_attachments_alt');
            $table->dropIndex('idx_attachments_filename');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('idx_tags_homepage');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
        });

        Schema::table('category_translations', function (Blueprint $table) {
            $table->dropIndex('idx_category_translations_name');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex('idx_sites_name');
        });
    }
};
