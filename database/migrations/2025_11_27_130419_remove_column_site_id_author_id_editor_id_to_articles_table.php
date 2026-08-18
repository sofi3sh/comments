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
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['author_id']);
            $table->dropForeign(['editor_id']);
            
            $table->dropColumn(['site_id', 'author_id', 'editor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->default(null);
            $table->unsignedBigInteger('author_id')->nullable()->default(null);
            $table->unsignedBigInteger('editor_id')->nullable()->default(null);
           
            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('editor_id')->references('id')->on('users')->cascadeSetNull();
        });
    }
};
