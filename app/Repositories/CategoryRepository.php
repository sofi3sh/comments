<?php

namespace App\Repositories;

use App\Models\Articles\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @param int $limit
     * @param int|null $siteId
     * @return Collection
     */
    public function getHomepageCategories(int $limit = 10, ?int $siteId = null): Collection
    {
//        if (!$siteId) {
//            $siteId = app('currentSite')->id;
//        }

        return Category::with('translations')
//            ->where('site_id', $siteId)                // todo for customization
            ->where('homepage', 1)
            ->limit($limit)
            ->get();
    }
}