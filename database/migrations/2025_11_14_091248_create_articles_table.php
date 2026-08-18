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
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('category_id')->nullable()->default(null);
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('editor_id')->nullable()->default(null);
            $table->string('slug',255)->unique();
            $table->string('thumbnail',255)->nullable()->default(null);
            $table->unsignedBigInteger('type_id')->nullable()->default(null);
            $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'moderation'])->default('draft');
            $table->timestamp('published_at')->nullable()->default(null);
            $table->string('source_url', 500)->nullable();
            $table->boolean('is_media')->default(false);
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeSetNull();
            $table->foreign('type_id')->references('id')->on('article_types')->cascadeSetNull();
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('editor_id')->references('id')->on('users')->cascadeSetNull();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
