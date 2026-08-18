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
        Schema::table('tags', function (Blueprint $table) {

            if (
                Schema::hasColumn('tags', 'topic')
                && ! Schema::hasColumn('tags', 'homepage')
            ) {
                $table->renameColumn('topic', 'homepage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {

            if (
                Schema::hasColumn('tags', 'homepage')
                && ! Schema::hasColumn('tags', 'topic')
            ) {
                $table->renameColumn('homepage', 'topic');
            }
        });
    }
};
