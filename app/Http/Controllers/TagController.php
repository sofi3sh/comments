<?php

namespace App\Http\Controllers;

use App\Facades\Seo;
use App\Repositories\ArticleRepository;
use App\Repositories\TagRepository;
use App\SEO\Pages\TagSeo;
use App\Support\LanguageSwitcherStore;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TagController
{
    public function show(
        Request $request,
        TagRepository $tagRepository,
        ArticleRepository $articleRepository,
    ) {
        $locale = app()->getLocale();

        $tag = $tagRepository->findTagBySlug(
            $request->route('slug'),
            $locale
        );

        abort_unless($tag !== null, 404);

        app(LanguageSwitcherStore::class)->set($tag);

        /** @var LengthAwarePaginator $articles */
        $articles = $articleRepository->getPublishedByTag(
            $tag,
            $locale,
            config('article.article.per_page')
        );

        Seo::set(TagSeo::make($tag))->paginate($articles);

        return view('list', [
            'title'    => $tag->title,
            'articles' => $articles,
            'paginate' => true,
        ]);

    }
}