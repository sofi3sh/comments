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
        Schema::create('attachment_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('attachment_id');
            $table->unsignedBigInteger('tag_id');

            $table->foreign('attachment_id')->references('id')->on('attachments')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
            $table->unique(['attachment_id', 'tag_id'], 'unique_attachment_tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment_tag');
    }
};
