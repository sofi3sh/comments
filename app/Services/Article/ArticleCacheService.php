<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\Settings\Locale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ArticleCacheService
{
    public const ARTICLES_WITH_ACTIONS_CACHE_PREFIX = 'articles_with_actions';

    public function forgetPage(Article $article, bool $reloadMeta = true)
    {
        $this->forgetPageWithRole($article, $reloadMeta);
        $this->forgetPagesData($article);
    }


    public function forgetPageWithRole(Article $article, bool $reloadMeta = true): void
    {
        if ((int) $article->type_id !== ArticleType::getTypeId(ArticleType::PAGE)) {
            return;
        }

        if ($reloadMeta || ! $article->relationLoaded('meta')) {
            $article->load('meta');
        }

        $article->meta
            ->where('field', 'page_role')
            ->pluck('value')
            ->filter()
            ->unique()
            ->each(fn (string $role) => $this->forgetPageRoleByRole($role));
    }


    public function forgetPagesData(Article $article)
    {
        if ((int) $article->type_id === ArticleType::getTypeId(ArticleType::PAGE)) {
            app(FooterPagesService::class)->forget();
        }
    }

    public function forgetPageRoleByRole(string $role): void
    {
        Cache::forget($this->pageRoleKey($role));
    }

    public function pageRoleKey(string $role): string
    {
        return "page_role_{$role}";
    }

    public function forgetArticlesWithActions(?string $locale = null): void
    {
        if ($locale !== null) {
            Cache::forget($this->articlesWithActionsVersionKey($locale));

            return;
        }

        foreach (Locale::getAvailableAsArr() as $availableLocale) {
            Cache::forget($this->articlesWithActionsVersionKey($availableLocale));
        }
    }

    public function articlesWithActionsHtmlKey(string $locale, string $host): string
    {
        $version = Cache::rememberForever(
            $this->articlesWithActionsVersionKey($locale),
            static fn (): string => Str::random(16),
        );

        return self::ARTICLES_WITH_ACTIONS_CACHE_PREFIX.
            ":html:locale:{$locale}:version:{$version}:host:".md5($host);
    }

    private function articlesWithActionsVersionKey(string $locale): string
    {
        return self::ARTICLES_WITH_ACTIONS_CACHE_PREFIX.":version:locale:{$locale}";
    }
}
