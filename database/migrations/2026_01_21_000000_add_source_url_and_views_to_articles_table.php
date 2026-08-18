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
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('published_at');
            }
            
            if (!Schema::hasColumn('articles', 'views')) {
                $table->unsignedInteger('views')->default(0)->after('source_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'views')) {
                $table->dropColumn('views');
            }
            
            if (Schema::hasColumn('articles', 'source_url')) {
                $table->dropColumn('source_url');
            }
        });
    }
};
