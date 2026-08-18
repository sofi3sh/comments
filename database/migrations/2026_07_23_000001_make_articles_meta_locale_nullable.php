<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles_meta', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->change();
        });

        DB::table('article_field_configurations')
            ->where('field_name', 'video_url')
            ->update(['field_name' => 'youtube_id']);
    }

    public function down(): void
    {
        DB::table('articles_meta')
            ->where('field', 'youtube_id')
            ->delete();

        DB::table('article_field_configurations')
            ->where('field_name', 'youtube_id')
            ->update(['field_name' => 'video_url']);

        Schema::table('articles_meta', function (Blueprint $table) {
            $table->string('locale', 5)->nullable(false)->change();
        });
    }
};
