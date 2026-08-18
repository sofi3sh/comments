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
        // First, drop foreign key constraint if exists
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
            DB::statement("ALTER TABLE `articles` DROP FOREIGN KEY `{$constraintName}`");
        }

        // Change status_id back to status enum
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'status_id')) {
                $table->dropColumn('status_id');
            }

            if (!Schema::hasColumn('articles', 'status')) {
                $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'moderation'])
                    ->default('draft')
                    ->after('type_id');
            }
        });

        // Drop status translations table
        Schema::dropIfExists('article_status_translations');
        
        // Drop statuses table
        Schema::dropIfExists('article_statuses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate statuses tables
        Schema::create('article_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('article_status_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('article_status_id');
            $table->string('locale', 5);
            $table->string('name', 255);
            $table->timestamps();

            $table->foreign('article_status_id')->references('id')->on('article_statuses')->cascadeOnDelete();
            $table->unique(['article_status_id', 'locale'], 'unique_article_status_locale');
        });

        // Change status back to status_id
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'status')) {
                $table->dropColumn('status');
            }

            if (!Schema::hasColumn('articles', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->after('type_id');
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
};
