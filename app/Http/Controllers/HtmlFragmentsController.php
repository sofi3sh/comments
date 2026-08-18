<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\ArticleTypeRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Services\Article\ArticleCacheService;
use App\Services\Article\FooterPagesService;
use App\Support\LastModifiedStore;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;

class HtmlFragmentsController extends Controller
{
    public function footerCategories(
        Request $request,
        CategoryRepositoryInterface $categories,
        ArticleTypeRepositoryInterface $types
    ): Response {

        $locale = (string) $request->route('locale');

        app()->setLocale($locale);

        $limit = config('app.footer_items_limit');

        return response()->view('partials.footer-categories', [
            'categories' => $categories->getHomepageCategories($limit),
            'types' => $types->getHomepageArticleTypes($limit),
        ]);
    }

    public function footerPages(
        Request $request,
        FooterPagesService $pagesService,
    ): Response {
        $locale = (string) $request->route('locale');

        app()->setLocale($locale);

        $pages = $pagesService->getForLocale($locale);

        setLastMod(collect($pages)->max('last_modified'));

        return response()->view('partials.footer-pages', [
            'pages' => $pages,
        ]);
    }

    public function articlesWithActions(Request $request): Response
    {
        $locale = (string) $request->route('locale');

        app()->setLocale($locale);

        $cache = app(ArticleCacheService::class);
        $fragment = Cache::remember(
            $cache->articlesWithActionsHtmlKey($locale, $request->getHost()),
            now()->addSeconds((int) config('views.articles_with_actions_cache_ttl', 600)),
            function () use ($locale): array {
                $html = Blade::render(
                    '<x-containers.articles-with-actions-container :locale="$locale" />',
                    ['locale' => $locale]
                );

                return [
                    'html' => $html,
                    'last_modified' => app(LastModifiedStore::class)->get(),
                ];
            },
        );

        setLastMod($fragment['last_modified']);

        return response($fragment['html']);
    }
}
