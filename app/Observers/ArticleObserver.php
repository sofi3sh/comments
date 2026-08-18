<?php

namespace App\Observers;

use App\Models\Articles\Article;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Services\Article\ArticleCacheService;

class ArticleObserver
{
    /**
     * Handle the Article "saved" event.
     */
    public function saved(Article $article): void
    {
        app(ArticleCacheService::class)->forgetArticlesWithActions();
        $this->updateTranslations($article);
    }

    public function deleted(Article $article): void
    {
        app(ArticleCacheService::class)->forgetArticlesWithActions();
    }

    /**
     * Handle the Article "synced" event (when markers are attached/detached).
     */
    public function pivotAttached(Article $article, string $relationName, array $pivotIds, array $pivotIdsAttributes): void
    {
        if ($relationName === 'markers') {
            $this->updateTranslations($article);
        }
    }

    /**
     * Handle the Article "synced" event (when markers are detached).
     */
    public function pivotDetached(Article $article, string $relationName, array $pivotIds): void
    {
        if ($relationName === 'markers') {
            $this->updateTranslations($article);
        }
    }

    /**
     * Update all translations: set `title_with_markers` from base `title` + markers. No stripping.
     */
    private function updateTranslations(Article $article): void
    {
        $article->load('markers');

        ArticleTranslation::where('article_id', $article->id)->get()->each(function (ArticleTranslation $translation) use ($article): void {
            $baseTitle = $translation->title;

            if ($baseTitle === null || $baseTitle === '') {
                if ($translation->title_with_markers !== null) {
                    ArticleTranslation::where('id', $translation->id)->update(['title_with_markers' => null]);
                }

                return;
            }

            $markers = $article->markers()
                ->where('is_active', true)
                ->get();

            $markers->load('translations');

            if ($markers->isEmpty()) {
                if ($translation->title_with_markers !== null) {
                    ArticleTranslation::where('id', $translation->id)->update(['title_with_markers' => null]);
                }

                return;
            }

            $markerNames = [];
            foreach ($markers as $marker) {
                $markerName = $marker->translate($translation->locale)?->name ?? '';

                if ($markerName !== '') {
                    $markerNames[] = $markerName;
                }
            }

            if ($markerNames === []) {
                if ($translation->title_with_markers !== null) {
                    ArticleTranslation::where('id', $translation->id)->update(['title_with_markers' => null]);
                }

                return;
            }

            $markersPrefix = implode(' ', $markerNames);
            $newTitleWithMarkers = $markersPrefix . ' ' . $baseTitle;

            if ($translation->title_with_markers !== $newTitleWithMarkers) {
                ArticleTranslation::where('id', $translation->id)->update(['title_with_markers' => $newTitleWithMarkers]);
            }
        });
    }
}
