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
        Schema::create('article_field_configurations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('field_name', 100); // title, excerpt, content, slug, etc.
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->integer('max_length')->nullable();
            $table->integer('min_length')->nullable();
            $table->json('validation_rules')->nullable(); // Додаткові правила валідації
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique('field_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_field_configurations');
    }
};

