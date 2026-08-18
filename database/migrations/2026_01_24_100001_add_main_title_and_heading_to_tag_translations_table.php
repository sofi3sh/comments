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
            $table->string('main_title', 255)->nullable()->after('title');
            $table->string('heading', 255)->nullable()->after('main_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tag_translations', function (Blueprint $table) {
            $table->dropColumn(['main_title', 'heading']);
        });
    }
};
