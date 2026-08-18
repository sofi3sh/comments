<?php

use App\Models\Articles\ArticleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_types', function (Blueprint $table) {
            $table->boolean('is_splittable')->default(false)->after('homepage');
        });

        DB::table('article_types')
            ->whereIn('code', [
                ArticleType::NEWS,
                ArticleType::ARTICLE,
                ArticleType::INTERVIEW,
                ArticleType::OPINION,
            ])
            ->update(['is_splittable' => true]);
    }

    public function down(): void
    {
        Schema::table('article_types', function (Blueprint $table) {
            $table->dropColumn('is_splittable');
        });
    }
};
