<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_translation_id')->constrained('article_translations')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('provider', 50)->default('content_watch');
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('uniqueness_percent', 5, 2)->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['article_translation_id', 'provider'], 'article_content_translation_provider_unique');
            $table->index(['article_id', 'locale']);
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_content');
    }
};
