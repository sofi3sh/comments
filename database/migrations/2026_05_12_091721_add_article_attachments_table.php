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
        Schema::create('article_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->foreignId('attachment_id')->constrained('attachments')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->unique([
                'owner_id',
                'owner_type',
                'attachment_id',
            ], 'article_attachments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_attachments');
    }
};
