<?php

namespace App\Http\Controllers;

use App\Models\Site\Site;
use App\Repositories\SeoRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function __construct(
        private readonly SeoRepository $seo,
    ) {}

    public function robots(): Response
    {
        return response($this->seo->robots($this->site()))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemapIndex(): Response
    {
        return response($this->seo->sitemapIndex($this->site()))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function sitemap(Request $request): Response
    {
        $locale = (string) $request->route('locale');
        $page = (int) $request->route('page');

        return response($this->seo->sitemap($this->site(), $locale, $page))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function newsSitemap(Request $request): Response
    {
        $locale = (string) $request->route('locale');

        return response($this->seo->newsSitemap($this->site(), $locale))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function site(): Site
    {
        return app('currentSite');
    }
}
