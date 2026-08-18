<?php

namespace App\Repositories\Interfaces;

interface ArticleTypeRepositoryInterface
{
    public function getHomepageArticleTypes(int $limit);
}