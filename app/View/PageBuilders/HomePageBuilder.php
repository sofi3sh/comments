<?php

namespace App\View\PageBuilders;

use App\Models\Articles\ArticlesBlockSetting;
use App\Models\Articles\Article;
use App\Services\Article\ArticlesBlockSettingsService;

class HomePageBuilder
{
    /** @var array<string, mixed> */
    private array $blockArticles = [];

    public function build(): array
    {
        return [
            'main'     => $this->buildMainContainer(),
            'swiper'   => $this->buildSwiperContainer(),
            'articles' => $this->buildArticlesContainer(),
            'latest'   => $this->buildLatestContainer(),
            'live'     => $this->buildLiveContainer(),
        ];
    }

    public function buildMainContainer(): array
    {
        $articles = $this->getArticlesForBlock(ArticlesBlockSetting::MAIN_CONTAINER_RIGHT, 5);
        $aiArticles = $this->getArticlesForBlock(ArticlesBlockSetting::MAIN_CONTAINER_LEFT, 4);

        return [
            'articles'    => $articles,
            'aiArticles'  => $aiArticles,
        ];
    }

    public function buildSwiperContainer(): array
    {
        $articles = $this->getArticlesForBlock(ArticlesBlockSetting::SWIPER_CONTAINER, 6);

        return [
            'articles' => $articles,
        ];
    }

    public function buildArticlesContainer(): array
    {
        $leftArticles = $this->getArticlesForBlock(ArticlesBlockSetting::ARTICLES_CONTAINER_LEFT, 4);
        $rightArticles = $this->getArticlesForBlock(ArticlesBlockSetting::ARTICLES_CONTAINER_RIGHT, 4);

        return [
            'leftArticles'  => $leftArticles,
            'leftArticlesCode' => ArticlesBlockSetting::ARTICLES_CONTAINER_LEFT,
            'rightArticles' => $rightArticles,
            'rightArticlesCode' => ArticlesBlockSetting::ARTICLES_CONTAINER_RIGHT,
        ];
    }

    public function buildLatestContainer(): array
    {
        $articles = $this->getArticlesForBlock(ArticlesBlockSetting::LATEST_MATERIALS, 4);

        return [
            'articles' => $articles,
        ];
    }

    public function buildLiveContainer(): array
    {
        $articles = $this->getArticlesForBlock(ArticlesBlockSetting::VIDEO_MATERIALS, 4);

        return [
            'articles' => $articles,
        ];
    }

    private function getArticlesForBlock(string $blockKey, int $fallbackLimit): mixed
    {
        $cacheKey = "{$blockKey}:{$fallbackLimit}";

        // Один блок може бути показаний у кількох контейнерах головної сторінки.
        // Повторно використовуємо результат у межах HTTP-запиту без другого читання кешу.
        if (! array_key_exists($cacheKey, $this->blockArticles)) {
            $this->blockArticles[$cacheKey] = app(ArticlesBlockSettingsService::class)
                // Для головної потрібні лише перші записи, без повного підрахунку вибірки.
                ->getArticlesForBlock($blockKey, $fallbackLimit, withPagination: false);
        }

        $articles = $this->blockArticles[$cacheKey];

        $timestamp = collect($articles)
            ->filter(fn (mixed $article): bool => $article instanceof Article)
            ->max(fn (Article $article): ?int => $article->updated_at?->getTimestamp());

        setLastMod($timestamp);

        return $articles;
    }
}
