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
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('_lft')->nullable()->default(null)->after('parent_id');
            $table->unsignedInteger('_rgt')->nullable()->default(null)->after('_lft');
            $table->unsignedInteger('_depth')->nullable()->default(null)->after('_rgt');

            $table->index(['_lft', '_rgt', '_depth']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['_lft', '_rgt', '_depth']);
            $table->dropColumn(['_lft', '_rgt', '_depth']);
        });
    }
};
