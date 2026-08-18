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
        Schema::create('markers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->text('icon')->nullable(); // class або svg/image
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_list')->default(false);
            $table->boolean('show_in_article')->default(true);
            $table->boolean('is_title_marker')->default(false);
            $table->string('position', 50)->nullable()->default('top'); // top, bottom, left, right
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markers');
    }
};

