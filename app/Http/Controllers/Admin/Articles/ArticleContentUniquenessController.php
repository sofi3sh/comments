<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleContent;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Services\Article\ArticleContentUniquenessService;
use Illuminate\Http\JsonResponse;

class ArticleContentUniquenessController extends Controller
{
    public function show(string $type, int $id, string $locale): JsonResponse
    {
        $translation = $this->translation($type, $id, $locale);
        $content = $translation->contentCheck()
            ->where('provider', ArticleContent::PROVIDER_CONTENT_WATCH)
            ->first();

        return response()->json($this->payload($content, $translation));
    }

    public function recheck(
        string $type,
        int $id,
        string $locale,
        ArticleContentUniquenessService $service
    ): JsonResponse {
        $translation = $this->translation($type, $id, $locale);
        $content = $service->syncPendingForTranslation($translation);

        return response()->json($this->payload($content, $translation));
    }

    private function translation(string $type, int $id, string $locale): ArticleTranslation
    {
        $typeId = ArticleType::query()
            ->where('code', $type)
            ->where('is_active', true)
            ->value('id');

        abort_unless($typeId, 404);

        $article = Article::query()
            ->where('type_id', $typeId)
            ->findOrFail($id);

        return ArticleTranslation::query()
            ->where('article_id', $article->id)
            ->where('locale', $locale)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(?ArticleContent $content, ArticleTranslation $translation): array
    {
        $response = $content?->response ?? [];
        $matches = is_array($response) && is_array($response['matches'] ?? null)
            ? $response['matches']
            : [];

        return [
            'article_id' => $translation->article_id,
            'article_translation_id' => $translation->id,
            'locale' => $translation->locale,
            'status' => $content?->status,
            'uniqueness_percent' => $content?->uniqueness_percent,
            'checked_at' => $content?->checked_at?->toDateTimeString(),
            'error_message' => $content?->error_message,
            'matches' => $matches,
            'has_result' => $content !== null,
        ];
    }
}
