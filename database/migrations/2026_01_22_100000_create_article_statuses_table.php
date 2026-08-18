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
        Schema::create('article_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique(); // draft, pending, published, rejected, moderation
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create translations table
        Schema::create('article_status_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('article_status_id');
            $table->string('locale', 5);
            $table->string('name', 255);
            $table->timestamps();

            $table->foreign('article_status_id')->references('id')->on('article_statuses')->cascadeOnDelete();
            $table->unique(['article_status_id', 'locale'], 'unique_article_status_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_status_translations');
        Schema::dropIfExists('article_statuses');
    }
};
