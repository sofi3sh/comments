<?php

namespace App\Repositories;

use App\Models\Articles\Article;
use App\Models\Articles\Tag;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleRepository
{

    /**
     * @param Tag $tag
     * @param string $locale
     * @param int $per_page
     * @return mixed
     */
    public function getPublishedByTag(
        Tag $tag,
        string $locale,
        int $per_page = 20
    ): LengthAwarePaginator
    {
        return Article::query()
            ->published()
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale)
                    ->whereNotNull('slug')
                    ->where('slug', '<>', '');
            })
            ->whereHas('tags', function ($query) use ($tag) {
                $query->where('tags.id', $tag->id);
            })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale)
                        ->whereNotNull('slug')
                        ->where('slug', '<>', '')
                        ->select(
                            'id',
                            'article_id',
                            'locale',
                            'title',
                            'excerpt',
                            'slug'
                        );
                },
                'category',
                'type',
                'authors'
            ])
            ->latest('published_at')
            ->paginate($per_page);
    }


    /**
     * @param int $categoryId
     * @param string $locale
     * @param int $per_page
     * @return LengthAwarePaginator
     */
    public function getPublishedByCategory(
        int $categoryId,
        string $locale,
        int $per_page = 20
    ): LengthAwarePaginator
    {
        return Article::query()
            ->published()
            ->where('category_id', $categoryId)
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale)
                    ->whereNotNull('slug')
                    ->where('slug', '<>', '');
            })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale)
                        ->whereNotNull('slug')
                        ->where('slug', '<>', '')
                        ->select(
                            'id',
                            'article_id',
                            'locale',
                            'title',
                            'excerpt',
                            'slug'
                        );
                },
                'category',
                'type',
                'authors'
            ])
            ->select(
                'id',
                'old_id',
                'type_id',
                'category_id',
                'views',
                'published_at',
                'created_at',
                'updated_at',
            )
            ->orderBy('published_at', 'desc')
            ->paginate($per_page);
    }


    /**
     * @param int $typeId
     * @param string $locale
     * @param int $per_page
     * @return LengthAwarePaginator
     */
    public function getPublishedByArticleType(
        int $typeId,
        string $locale,
        int $per_page = 20
    ): LengthAwarePaginator
    {
        return Article::query()
            ->published()
            ->where('type_id', $typeId)
            ->translatedIn($locale)
            ->withTranslation($locale)
            ->with([
                'category',
                'type',
            ])
            ->select(
                'id',
                'type_id',
                'category_id',
                'views',
                'published_at',
                'created_at',
                'updated_at',
            )
            ->orderByDesc('published_at')
            ->paginate($per_page);
    }


    /**
     * @param User $author
     * @param string $locale
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedByAuthor(
        User $author,
        string $locale,
        int $perPage
    ): LengthAwarePaginator
    {
        return $author->articlesAuthor()
            ->published()
            ->whereIn('type_id', Article::ARTICLE_TYPE_IDS)
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale);
            })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale)
                        ->select(
                            'id',
                            'article_id',
                            'locale',
                            'title',
                            'excerpt',
                            'slug'
                        );
                },
                'category',
                'type',
                'authors',
            ])
            ->select(
                'articles.id',
                'articles.old_id',
                'articles.type_id',
                'articles.category_id',
                'articles.views',
                'articles.published_at',
                'articles.created_at',
                'articles.updated_at'
            )
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }



    /**
     * @param User $editor
     * @param string $locale
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedByEditor(
        User $editor,
        string $locale,
        int $perPage
    ): LengthAwarePaginator
    {
        return $editor->articlesEditor()
            ->published()
            ->whereIn('type_id', Article::ARTICLE_TYPE_IDS)
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale);
            })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale)
                        ->select(
                            'id',
                            'article_id',
                            'locale',
                            'title',
                            'excerpt',
                            'slug'
                        );
                },
                'category',
                'type',
                'editors',
            ])
            ->select(
                'articles.id',
                'articles.old_id',
                'articles.type_id',
                'articles.category_id',
                'articles.views',
                'articles.published_at',
                'articles.created_at',
                'articles.updated_at',
            )
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }
}