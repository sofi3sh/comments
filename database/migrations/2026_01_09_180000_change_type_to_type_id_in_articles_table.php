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
            if (!Schema::hasColumn('articles', 'type_id')) {
                $table->unsignedBigInteger('type_id')->nullable()->after('thumbnail');
            }
            

            if (Schema::hasColumn('articles', 'type')) {
                $table->dropColumn('type');
            }
        });

        Schema::table('articles', function (Blueprint $table) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'articles' 
                AND COLUMN_NAME = 'type_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            
            if (empty($foreignKeys)) {
                $table->foreign('type_id')
                    ->references('id')
                    ->on('article_types')
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
                AND COLUMN_NAME = 'type_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            
            if (!empty($foreignKeys)) {
                $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
                $table->dropForeign([$constraintName]);
            }
            
            if (!Schema::hasColumn('articles', 'type')) {
                $table->string('type', 50)->nullable()->after('thumbnail');
            }
            
            if (Schema::hasColumn('articles', 'type_id')) {
                $table->dropColumn('type_id');
            }
        });
    }
};
