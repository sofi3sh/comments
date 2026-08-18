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
        Schema::create('seo_meta_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('seo_meta_id');
            $table->string('locale', 5);
            $table->string('meta_title',255)->nullable()->default(null);
            $table->string('meta_description',500)->nullable()->default(null);
            $table->string('meta_keywords',500)->nullable()->default(null);
            $table->string('og_title',255)->nullable()->default(null);
            $table->string('og_description',500)->nullable()->default(null);
            $table->string('og_image',255)->nullable()->default(null);
            $table->timestamps();

            $table->foreign('seo_meta_id')->references('id')->on('seo_meta')->cascadeOnDelete();
            $table->unique(['seo_meta_id', 'locale'], 'unique_seo_meta_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_meta_translations');
    }
};
