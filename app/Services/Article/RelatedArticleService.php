<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;

class RelatedArticleService
{
    private const ALLOWED_TYPE_CODES = [
        ArticleType::NEWS,
        ArticleType::ARTICLE,
        ArticleType::INTERVIEW,
    ];

    private const VIDEO_TYPE_CODES = [
        ArticleType::VIDEO,
    ];

    private const CATEGORIZED_RELATED_TYPE_CODES = [
        ArticleType::NEWS,
        ArticleType::ARTICLE,
        ArticleType::INTERVIEW,
        ArticleType::VIDEO,
    ];

    /**
     * Return the previous related published article.
     *
     * News, articles and interviews are traversed within their category. Video
     * publications with a category use the same category-based chain; videos
     * without one form a common chronological video sequence. The lookup uses
     * keyset ordering by (published_at, id), so identical publication dates are
     * still traversed in a stable order.
     */
    public function previousRelated(Article $article): ?Article
    {
        $isVideo = $this->isVideo($article);

        if (! $this->isAvailableFor($article) || ! $article->published_at || (! $isVideo && $article->category_id === null)) {
            return null;
        }

        $query = Article::query()
            ->published()
            ->whereHas('translations', function ($query) {
                $query->where('locale', app()->getLocale())
                    ->whereNotNull('slug')
                    ->where('slug', '<>', '');
            })
            ->where(function ($query) use ($article) {
                $query->where('published_at', '<', $article->published_at)
                    ->orWhere(function ($query) use ($article) {
                        $query->where('published_at', '=', $article->published_at)
                            ->where('id', '<', $article->id);
                    });
            })
            ->whereIn('type_id', $this->relatedTypeIds($article))
            ->with(['category', 'translations', 'type'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($article->category_id !== null) {
            $query->where('category_id', $article->category_id);
        }

        return $query->first();
    }

    private function isAvailableFor(Article $article): bool
    {
        return in_array((int) $article->type_id, $this->typeIdsFor(self::CATEGORIZED_RELATED_TYPE_CODES), true);
    }

    /**
     * @return list<int>
     */
    private function relatedTypeIds(Article $article): array
    {
        return $this->typeIdsFor(
            match (true) {
                ! $this->isVideo($article) => self::ALLOWED_TYPE_CODES,
                $article->category_id !== null => self::CATEGORIZED_RELATED_TYPE_CODES,
                default => self::VIDEO_TYPE_CODES,
            }
        );
    }

    private function isVideo(Article $article): bool
    {
        return (int) $article->type_id === ArticleType::getTypeId(ArticleType::VIDEO);
    }

    /**
     * @param list<string> $typeCodes
     * @return list<int>
     */
    private function typeIdsFor(array $typeCodes): array
    {
        return array_values(array_filter(array_map(
            fn (string $code) => ArticleType::getTypeId($code),
            $typeCodes
        )));
    }
}
