<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->after('type_id');
            }

            if (Schema::hasColumn('articles', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('articles', function (Blueprint $table) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'articles' 
                AND COLUMN_NAME = 'status_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            
            if (empty($foreignKeys)) {
                $table->foreign('status_id')
                    ->references('id')
                    ->on('article_statuses')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'articles' 
                AND COLUMN_NAME = 'status_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            
            if (!empty($foreignKeys)) {
                $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
                $table->dropForeign([$constraintName]);
            }
            
            if (!Schema::hasColumn('articles', 'status')) {
                $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'moderation'])->default('draft')->after('type_id');
            }
            
            if (Schema::hasColumn('articles', 'status_id')) {
                $table->dropColumn('status_id');
            }
        });
    }
};
