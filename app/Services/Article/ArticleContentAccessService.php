<?php

namespace App\Services\Article;

class ArticleContentAccessService
{
    public function canViewFullContent(): bool
    {
        return backpack_auth()->check();
    }
}
