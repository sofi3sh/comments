<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Services\Article\ArticleUrlBuilder;
use App\Support\LegacyRedirectRoutes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyArticleRedirectController extends Controller
{
    private const ALLOWED_TYPES = [
        ArticleType::NEWS,
        ArticleType::OPINION,
        ArticleType::ARTICLE,
        ArticleType::INTERVIEW,
        ArticleType::PRESS_RLS,
        ArticleType::INFOGRAPHICS,
    ];


    public function typeList(Request $request): RedirectResponse
    {
        $type = ArticleType::codeFromRoute((string) $request->route('type'));

        abort_unless(in_array($type, self::ALLOWED_TYPES, true), 404);

        $routeParams = [
            'locale' => app()->getLocale(),
            'type' => ArticleType::codeForRoute($type),
        ];

        if ($request->has('page')) {
            $routeParams['page'] = (int) $request->query('page');
        }

        return redirect()->route(LegacyRedirectRoutes::TYPE_LIST, $routeParams, 301);
    }


    public function article(Request $request, ArticleUrlBuilder $urlBuilder): RedirectResponse
    {
        $article = $this->findArticle($request);
        $url = $urlBuilder->urlForLocale($article, app()->getLocale());

        abort_if(! $url, 404);

        return redirect()->to($url, 301);
    }


    private function findArticle(Request $request): Article
    {
        $type   = ArticleType::codeFromRoute((string) $request->route('type'));
        $typeId = ArticleType::getTypeId($type);

        $id = (int) $request->route('id');

        $query = Article::query()
            ->published()
            ->where('old_id', $id)
            ->whereHas('translations', fn ($translationQuery) => $translationQuery
                ->where('locale', app()->getLocale())
            )
            ->with(['type', 'category', 'translations']);

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        } elseif (in_array($type, ArticleType::TYPES_CAT, true)) {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $type));
        } else {
            abort(404);
        }

        $subcategory = $request->route('subcategory');
        if (! empty($subcategory)) {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $subcategory));
        }

        return $query->firstOrFail();
    }
}
