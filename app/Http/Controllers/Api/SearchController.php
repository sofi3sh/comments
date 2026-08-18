<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Models\Articles\Translate\ArticleTranslation;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (!$query || mb_strlen($query) < 2) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1,
            ]);
        }

        $locale = $request->route('locale', 'uk'); //todo

        $filter = $request->input('filter', 'all');

        $dateFrom = null;
        $dateTo   = null;

        if ($filter === 'recent') {
            $dateFrom = now()->subDays(config('scout.meilisearch.recent_days', 3))->timestamp;
            $dateTo   = now()->timestamp;
        }

        $sort     = $request->input('sort', 'desc');
        $page     = max((int) $request->input('page', 1), 1);
        $perPage  = config('scout.meilisearch.per_page', 20);

        $filters = ['locale = "' . $locale . '"'];

        if ($dateFrom && $dateTo) {
            $filters[] = "published_at >= $dateFrom";
            $filters[] = "published_at <= $dateTo";
        }

        $results = ArticleTranslation::search($query, function ($meili, $query, $options) use ($filters, $page, $perPage, $sort) {
            $options['filter'] = implode(' AND ', $filters);
            $options['sort']   = ['published_at:' . ($sort === 'asc' ? 'asc' : 'desc')];
            $options['limit']  = $perPage;
            $options['offset'] = ($page - 1) * $perPage;
            $options['matchingStrategy'] = 'all';

            return $meili->search($query, $options);
        })->raw();

        $hits  = $results['hits'] ?? [];
        $total = $results['estimatedTotalHits'] ?? 0;
        $lastPage = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        $data = collect($hits)->map(function ($item) {

            $article = Article::find($item['article_id'] ?? null);

            // Meilisearch may temporarily contain a document for a deleted article
            // (or an article outside the current site scope). Do not let one stale
            // hit make the whole search request fail.
            if (!$article) {
                return null;
            }

            $excerpt = $article->excerpt ?? \Str::limit(strip_tags($item['text'] ?? ''), 200);

            $viewData = [
                'id'         => $item['id'] ?? '',
                'article_id' => $item['article_id'] ?? '',
                'title'      => $item['title'] ?? '',
                'url'        => $article->getArticleUrl(),
                'article'    => $article,
                'thumbnail'  => $article->getCoverUrl('card_lg'),
                'thumbnailSrcset' => $article->getCoverSrcset(['card_sm', 'card_lg']),
                'thumbnailSizes'  => '(max-width: 768px) 100vw, 640px',
                'excerpt'    => $excerpt,
                'viewsCount' => $article->views,
                'publishedAt'   => $article->published_at?->format('H:i, d-m-y') ?? '-',
                'categoryTitle' => $article->category?->name ?? '-'
            ];

            return [
                'html' => view('components.cards.search-article-card', $viewData)->render(),
            ];
        })->filter()->values();

        return response()->json([
            'data'  => $data,
            'total' => $total,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'per_page'     => $perPage,
        ]);
    }
}
