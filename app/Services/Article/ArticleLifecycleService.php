<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use Illuminate\Support\Facades\DB;

class ArticleLifecycleService
{
    public function delete(Article $article): bool
    {
        return DB::transaction(
            fn (): bool => (bool) $article->delete()
        );
    }

    public function restore(Article $article): bool
    {
        return DB::transaction(
            fn (): bool => (bool) $article->restore()
        );
    }
}
