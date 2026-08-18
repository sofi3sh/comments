<?php

namespace App\Services\Article;

use App\Models\Articles\Article;

class ArticleUrlBuilder
{
    /**
     * Build the public frontend URL for an article locale.
     * Usage: frontend URL generation.
     *
     * @param Article $article
     * @param string $locale
     * @return string|null
     */
    public function urlForLocale(Article $article, string $locale): ?string
    {
        $params = $this->routeParamsForUrl($article, $locale);

        return $this->routeFromParams($params);
    }


    /**
     * Build a Laravel route URL or relative path from prepared article params.
     * Usage: frontend URL generation.
     */
    private function routeFromParams(?array $params): ?string
    {
        if ($params === null) {
            return null;
        }
        if (! empty($params['subcategory'])) {
            return route("locale.front.article.show.with.subcategory", $params);
        } else {
            return route("locale.front.article.show", $params);
        }
    }


    /**
     * Build frontend article route params without calling Laravel's route()
     * helper. This keeps URL data available in admin, queue, and CLI contexts.
     *
     * Usage: shared by frontend URL generation and static invalidation paths.
     *
     * @param Article $article
     * @param string|null $locale
     * @return array|null
     */
    public function routeParamsForUrl(Article $article, ?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();

        $slug = $this->slugFor($article, $locale);

        if (! $slug) {
            return null;
        }

        return $this->routeParams($article, $locale, $slug);
    }


    /**
     * Build article route params using an explicit locale and slug.
     * Usage: shared by frontend URL generation and static invalidation paths.
     */
    protected function routeParams(Article $article, string $locale, ?string $slug): ?array
    {
        $type = $this->typeCodeFor($article);
        $id = $article->id;

        if (empty($locale) || empty($type) || empty($slug) || empty($id)) {
            return null;
        }

        $params = [
            'domain' => $this->domain(),
            'locale' => $locale,
            'type'   => $type,
            'slug'   => $slug,
            'id'     => $id,
        ];

        $categorySlug = $this->categorySlugFor($article);

        if (! empty($categorySlug)) {
            $params['subcategory'] = $categorySlug;
        }

        return $params;
    }


    /**
     * Build the current static storage path for an article locale.
     * Usage: static invalidation path generation.
     */
    public function pathFor(Article $article, ?string $locale = null): ?string
    {
        return $this->pathFromParams(
            $this->routeParamsForUrl($article, $locale)
        );
    }


    /**
     * for old
     *
     * Build a static storage path from explicit article URL data.
     * Usage: static invalidation path generation.
     */
    public function pathForData(Article $article, string $locale, ?string $slug): ?string
    {
        return $this->pathFromParams(
            $this->routeParams($article, $locale, $slug)
        );
    }


    /**
     * Convert article route params into a static storage path.
     * Usage: static invalidation path generation.
     */
    private function pathFromParams(?array $params): ?string
    {
        if ($params === null) {
            return null;
        }

        $segments = [
            $params['locale'],
            $params['type'],
        ];

        if (! empty($params['subcategory'])) {
            $segments[] = $params['subcategory'];
        }

        $segments[] = $params['slug'].'-'.$params['id'].'.html';

        return implode('/', $segments);
    }


    /**
     * Resolve the domain required by domain-based frontend routes.
     * Usage: frontend URL generation.
     */
    private function domain(): string
    {
        $currentSite = app()->bound('currentSite') ? app('currentSite') : null;

        return $currentSite?->domain
            ?? request()->route('domain')
            ?? request()->getHost();
    }


    /**
     * Get the article slug for a locale.
     * Usage: shared by frontend URL generation and static invalidation paths.
     */
    public function slugFor(Article $article, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $article->translate($locale)?->slug;
    }


    /**
     * Get the article type code used in frontend URLs.
     * Usage: shared by frontend URL generation and static invalidation paths.
     */
    public function typeCodeFor(Article $article): ?string
    {
        $article->loadMissing('type');

        return $article->type?->code;
    }


    /**
     * Get the article category slug used as an optional URL segment.
     * Usage: shared by frontend URL generation and static invalidation paths.
     */
    public function categorySlugFor(Article $article): ?string
    {
        if ($article->category_id) {
            $article->loadMissing('category');
        }

        return $article->category?->slug;
    }
}
