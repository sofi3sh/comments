<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\SEO\SeoManager set(\App\SEO\Contracts\SeoSource $source)
 * @method static \App\SEO\SeoManager paginate(\Illuminate\Pagination\LengthAwarePaginator $paginator)
 * @method static \App\SEO\Data\SeoData data()
 *
 * @see \App\SEO\SeoManager
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\SEO\SeoManager::class;
    }
}
