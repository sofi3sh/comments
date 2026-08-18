<?php

namespace App\Services\Article;

use App\Exceptions\AdminArticleTitleSearchException;
use App\Models\Articles\Translate\ArticleTranslation;
use Throwable;

final readonly class AdminArticleTitleSearchService
{
    /**
     * @return list<int>
     */
    public function findArticleIds(string $searchTerm, ?int $typeId = null): array
    {
        $searchTerm = trim($searchTerm);

        if ($searchTerm === '') {
            return [];
        }

        try {
            $results = ArticleTranslation::search(
                $searchTerm,
                function ($meili, $query, $options) use ($typeId) {
                    if ($typeId !== null) {
                        $options['filter'] = 'type_id = ' . $typeId;
                    }

                    $options['limit'] = $this->limit();
                    $options['matchingStrategy'] = 'all';
                    $options['attributesToSearchOn'] = ['title'];

                    return $meili->search($query, $options);
                }
            )->raw();

            return $this->extractArticleIds($results['hits'] ?? []);
        } catch (Throwable $exception) {
            throw new AdminArticleTitleSearchException(
                'Admin article title search failed.',
                previous: $exception
            );
        }
    }

    private function limit(): int
    {
        return max(1, (int) config('scout.meilisearch.admin_title_limit', 1000));
    }

    /**
     * @param mixed $items
     * @return list<int>
     */
    private function extractArticleIds(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $ids = [];

        foreach ($items as $item) {
            $id = is_array($item) ? ($item['article_id'] ?? null) : null;
            $id = (int) $id;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
