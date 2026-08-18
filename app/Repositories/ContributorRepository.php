<?php

namespace App\Repositories;

use App\Models\Articles\Article;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ContributorRepository
{
    private const EDITORS_CACHE_VERSION_KEY = 'contributors:editors:version';

    public function getEditors(
        string $locale,
        int $perPage,
        int $page
    ): LengthAwarePaginator
    {
        return Cache::rememberForever(
            $this->editorsCacheKey($locale, $perPage, $page),
            function () use ($locale, $perPage, $page): LengthAwarePaginator {
                return User::query()
                    ->withTranslation($locale)
                    ->whereHas('articlesEditor', function ($q) {
                        $q->whereIn('type_id', Article::ARTICLE_TYPE_IDS);
                    })
                    ->orderBy('id')
                    ->paginate($perPage, ['*'], 'page', $page);
            }
        );
    }

    public function invalidateEditors(): void
    {
        Cache::forever(
            self::EDITORS_CACHE_VERSION_KEY,
            $this->editorsCacheVersion() + 1
        );
    }

    private function editorsCacheKey(string $locale, int $perPage, int $page): string
    {
        return 'contributors:editors:'
            . $this->editorsCacheVersion()
            . ":{$locale}:{$perPage}:{$page}";
    }

    private function editorsCacheVersion(): int
    {
        return (int) Cache::rememberForever(
            self::EDITORS_CACHE_VERSION_KEY,
            fn (): int => 1
        );
    }
}
