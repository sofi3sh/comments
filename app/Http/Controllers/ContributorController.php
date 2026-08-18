<?php

namespace App\Http\Controllers;

use App\Facades\Seo;
use App\Models\User\User;
use App\Repositories\ArticleRepository;
use App\Repositories\ContributorRepository;
use App\SEO\Pages\ContributorSeo;
use App\SEO\Pages\EditorsSeo;
use Illuminate\Http\Request;

class ContributorController extends Controller
{
    /**
     * @param Request $request
     * @param ArticleRepository $repository
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function author(Request $request, ArticleRepository $repository)
    {
        $id   = (int)$request->route('id');
        $author = User::findOrFail($id);

        /** if old url doesnt have slug */
        $slug = $request->route('slug');
        if (!$slug) {

            $locale = app()->getLocale();
            $routeName = $locale === 'ua' ? 'ua.old.author' : 'old.author';

            return redirect()->route($routeName, [
                'id' => $id,
                'slug' => $author->slug,
            ], 301);
        }

        $articles = $repository->getPublishedByAuthor(
            $author,
            app()->getLocale(),
            config('article.article.per_page')
        );

        Seo::set(ContributorSeo::make($author, 'author'));

        return view('author', [
            'title'    => __('Author'),
            'author'   => $author,
            'articles' => $articles,
            'paginate' => true,
        ]);
    }


    /**
     * @param Request $request
     * @param ArticleRepository $repository
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editor(Request $request, ArticleRepository $repository)
    {
        $id   = (int)$request->route('id');
        $editor = User::findOrFail($id);

        /** if old url has extra params */
        $params = $request->query();
        $allowed = ['id', 'translit', 'page'];
        $extraParams = array_diff(array_keys($params), $allowed);

        $routeParams = [
            'id'   => $id,
            'slug' => $editor->slug,
        ];

        if ($request->has('page')) {
            $routeParams['page'] = (int) $request->query('page');
        }

        if (!empty($extraParams)) {
            return redirect()->route('old.editor', $routeParams, 301);
        }

        /** if old url doesnt have slug */
        $slug = $request->route('slug');
        if (!$slug) {

            $locale = app()->getLocale();
            $routeName = $locale === 'ua' ? 'ua.old.editor' : 'old.editor';

            return redirect()->route($routeName, [
                'id' => $id,
                'slug' => $editor->slug,
            ], 301);
        }

        $articles = $repository->getPublishedByEditor(
            $editor,
            app()->getLocale(),
            config('article.article.per_page')
        );

        Seo::set(ContributorSeo::make($editor, 'editor'));

        return view('editor', [
            'title'    => __('Editor'),
            'editor'   => $editor,
            'articles' => $articles,
            'paginate' => true,
        ]);
    }


    public function editors(Request $request, ContributorRepository $repository)
    {
        $editors = $repository->getEditors(
            app()->getLocale(),
            config('article.article.per_page'),
            max(1, (int) $request->query('page', 1))
        );

        Seo::set(EditorsSeo::make())->paginate($editors);

        return view('editors', compact('editors'));
    }
}
