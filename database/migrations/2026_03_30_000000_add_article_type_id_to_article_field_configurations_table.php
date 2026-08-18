<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article_field_configurations')) {
            return;
        }

        Schema::table('article_field_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('article_field_configurations', 'article_type_id')) {
                $table->unsignedBigInteger('article_type_id')->nullable()->after('field_name');

                $table->foreign('article_type_id', 'afc_article_type_id_foreign')
                    ->references('id')
                    ->on('article_types')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('article_field_configurations')) {
            return;
        }

        Schema::table('article_field_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('article_field_configurations', 'article_type_id')) {
                $table->dropForeign('afc_article_type_id_foreign');
                $table->dropColumn('article_type_id');
            }
        });
    }
};

