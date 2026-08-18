<?php

namespace App\Observers;

use App\Models\Articles\Translate\ArticleTranslation;
use App\Services\Article\ArticleContentUniquenessService;
use App\Services\Article\ArticleCacheService;
use App\Services\Article\StaticInvalidateService;
use Illuminate\Support\Facades\DB;

class ArticleTranslationObserver
{
    public function __construct(
        private readonly ArticleContentUniquenessService $articleContentUniquenessService,
        private readonly StaticInvalidateService $staticInvalidateService,
    ) {}

    /**
     * Handle the ArticleTranslation "saving" event.
     */
    public function saving(ArticleTranslation $articleTranslation): void
    {
        if (!$articleTranslation->article) {
            return;
        }

        $baseTitle = $articleTranslation->title;
        if ($baseTitle === null || $baseTitle === '') {
            $articleTranslation->title_with_markers = null;

            return;
        }

        $markers = $articleTranslation->article->markers()
            ->where('is_active', true)
            ->get();

        $markers->load('translations');

        if ($markers->isEmpty()) {
            $articleTranslation->title_with_markers = null;

            return;
        }

        $markerNames = [];
        foreach ($markers as $marker) {
            $markerName = $marker->translate($articleTranslation->locale)?->name ?? '';
            if ($markerName !== '') {
                $markerNames[] = $markerName;
            }
        }

        if ($markerNames === []) {
            $articleTranslation->title_with_markers = null;

            return;
        }

        $markersPrefix = implode(' ', $markerNames);
        $articleTranslation->title_with_markers = $markersPrefix . ' ' . $baseTitle;
    }

    public function updating(ArticleTranslation $articleTranslation): void
    {
        if ($articleTranslation->isDirty('slug')) {
            $this->staticInvalidateService->invalidateOriginalTranslationPath($articleTranslation);
        }
    }

    public function updated(ArticleTranslation $articleTranslation): void
    {
        if (!$this->shouldInvalidateStatic($articleTranslation)) {
            return;
        }

        if ($this->staticInvalidateService->isArticleInvalidated($articleTranslation->article_id)) {
            return;
        }

        $this->staticInvalidateService->invalidateTranslation($articleTranslation);
    }

    public function saved(ArticleTranslation $articleTranslation): void
    {
        app(ArticleCacheService::class)->forgetArticlesWithActions($articleTranslation->locale);

        if (
            !$articleTranslation->wasRecentlyCreated
            && !$articleTranslation->wasChanged(['content', 'content_html'])
        ) {
            return;
        }

        $articleTranslation->article?->forgetContentCache($articleTranslation->locale);

        $this->articleContentUniquenessService->syncPendingForTranslation($articleTranslation);
    }

    public function deleting(ArticleTranslation $articleTranslation): void
    {
        $article = $articleTranslation->article;
        $locale = $articleTranslation->locale;

        DB::afterCommit(function () use ($article, $articleTranslation, $locale): void {
            app(ArticleCacheService::class)->forgetArticlesWithActions($locale);
            $article?->forgetContentCache($locale);
            $this->staticInvalidateService->invalidateOriginalTranslationPath($articleTranslation);
        });
    }

    private function shouldInvalidateStatic(ArticleTranslation $articleTranslation): bool
    {
        return $articleTranslation->wasChanged([
            'title',
            'title_with_markers',
            'excerpt',
            'content',
            'content_html',
            'slug',
        ]);
    }
}
