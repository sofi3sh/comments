<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Services\Article\ArticleUrlBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyDossierRedirectController extends Controller
{
    public function show(Request $request, ArticleUrlBuilder $urlBuilder): RedirectResponse
    {
        $type = (string) $request->route('type');
        $id = (int) $request->route('id');
        $typeId = ArticleType::getTypeId($type);

        abort_if($typeId === null, 404);

        $article = Article::query()
            ->published()
            ->where('type_id', $typeId)
            ->where('old_id', $id)
            ->whereHas('translations', fn ($translationQuery) => $translationQuery
                ->where('locale', app()->getLocale())
            )
            ->with(['type', 'category', 'translations'])
            ->firstOrFail();

        $url = $urlBuilder->urlForLocale($article, app()->getLocale());

        abort_if(! $url, 404);

        return redirect()->to($url, 301);
    }

    public function dossier(Request $request): RedirectResponse
    {
        return redirect()->route('locale.dossier', [
            'locale' => app()->getLocale(),
        ], 301);
    }
}
