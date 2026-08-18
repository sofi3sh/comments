<?php

namespace App\Repositories;

use App\Models\Articles\ArticleType;
use App\Repositories\Interfaces\ArticleTypeRepositoryInterface;

class ArticleTypeRepository implements ArticleTypeRepositoryInterface
{
    public function getHomepageArticleTypes(int $limit)
    {
        return ArticleType::allCached()
            ->where('is_active', 1)
            ->where('homepage', 1)
            ->take($limit);
    }
}