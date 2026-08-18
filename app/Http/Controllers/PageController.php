<?php

namespace App\Http\Controllers;

use App\Facades\Seo;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\SEO\Pages\PageSeo;
use App\Services\Article\ArticleCacheService;
use App\Services\Article\ArticleContentService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(Request $request, ArticleCacheService $articleCacheService)
    {
        $role = $request->route('role');
        $pageTypeId = ArticleType::getTypeId(ArticleType::PAGE);

        $article = cache()->rememberForever($articleCacheService->pageRoleKey($role), function () use ($role, $pageTypeId) {
            return Article::query()
                ->published()
                ->where('type_id', $pageTypeId)
                ->whereHas('meta', fn($q) =>
                    $q->where('field', 'page_role')->where('value', $role)
                )
                ->firstOrFail();
        });

        $contentService = new ArticleContentService();
        $content = $contentService->getContentHtml($article);

        Seo::set(PageSeo::make($article));

        return view('page', [
            'content' => $content,
            'title'   => $article->title,
        ]);
    }
}