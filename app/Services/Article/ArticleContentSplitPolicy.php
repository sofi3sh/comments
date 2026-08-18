<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;

class ArticleContentSplitPolicy
{
    public function shouldSplitArticle(Article $article): bool
    {
        return $this->shouldSplitType($this->resolveTypeCode($article));
    }

    public function shouldSplitType(?string $type): bool
    {
        if ($type === null) {
            return false;
        }

        return in_array(
            ArticleType::codeFromRoute($type),
            ArticleType::contentSplitEnabledCodes(),
            true
        );
    }

    private function resolveTypeCode(Article $article): ?string
    {
        if ($article->relationLoaded('type')) {
            return $article->type?->code;
        }

        if (empty($article->type_id)) {
            return null;
        }

        return ArticleType::findCached((int) $article->type_id)?->code;
    }
}
