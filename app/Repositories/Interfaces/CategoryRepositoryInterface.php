<?php

namespace App\Repositories\Interfaces;

interface CategoryRepositoryInterface
{
    public function getHomepageCategories(int $limit, ?int $siteId);
}