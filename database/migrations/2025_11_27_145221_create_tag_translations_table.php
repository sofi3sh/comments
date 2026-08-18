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
        Schema::create('tag_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tag_id');
            $table->string('slug', 255)->unique();
            $table->string('locale', 5);
            $table->string('title', 255);
            $table->timestamps();

            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
            $table->unique(['tag_id', 'locale'], 'unique_tag_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_translations');
    }
};
