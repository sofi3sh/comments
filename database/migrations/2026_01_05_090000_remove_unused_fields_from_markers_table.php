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
        Schema::table('markers', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'show_in_list',
                'show_in_article',
                'is_title_marker',
                'position',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('markers', function (Blueprint $table) {
            $table->string('name', 255)->after('id');
            $table->boolean('show_in_list')->default(false)->after('is_active');
            $table->boolean('show_in_article')->default(true)->after('show_in_list');
            $table->boolean('is_title_marker')->default(false)->after('show_in_article');
            $table->string('position', 50)->nullable()->default('top')->after('is_title_marker');
        });
    }
};

