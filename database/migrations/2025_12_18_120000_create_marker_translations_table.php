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
        Schema::create('marker_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('marker_id');
            $table->string('locale', 5);
            $table->string('name', 255);
            $table->timestamps();

            $table->foreign('marker_id')->references('id')->on('markers')->cascadeOnDelete();
            $table->unique(['marker_id', 'locale'], 'unique_marker_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marker_translations');
    }
};

