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
        Schema::create('article_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique(); // news, interview, analytics, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create translations table
        Schema::create('article_type_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('article_type_id');
            $table->string('locale', 5);
            $table->string('name', 255);
            $table->timestamps();

            $table->foreign('article_type_id')->references('id')->on('article_types')->cascadeOnDelete();
            $table->unique(['article_type_id', 'locale'], 'unique_article_type_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_type_translations');
        Schema::dropIfExists('article_types');
    }
};

