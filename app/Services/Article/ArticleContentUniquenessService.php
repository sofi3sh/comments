<?php

namespace App\Services\Article;

use App\Models\Articles\ArticleContent;
use App\Models\Articles\Translate\ArticleTranslation;
use Illuminate\Support\Carbon;

final readonly class ArticleContentUniquenessService
{
    public function __construct(
        private ArticleContentTextExtractor $textExtractor,
        private ContentWatchClient $client,
    ) {}

    public function syncPendingForTranslation(ArticleTranslation $translation): ?ArticleContent
    {
        if (! $this->shouldCheckLocale($translation->locale)) {
            return null;
        }

        $text = $this->textExtractor->extract($translation);

        if ($text === null) {
            return null;
        }

        $hash = $this->textExtractor->hash($text);

        $content = ArticleContent::query()->firstOrNew([
            'article_translation_id' => $translation->id,
            'provider' => ArticleContent::PROVIDER_CONTENT_WATCH,
        ]);

        if (
            $content->exists
            && $content->content_hash === $hash
            && in_array($content->status, [ArticleContent::STATUS_CHECKED, ArticleContent::STATUS_CHECKING], true)
        ) {
            return $content;
        }

        $content->fill([
            'article_id' => $translation->article_id,
            'locale' => $translation->locale,
            'provider' => ArticleContent::PROVIDER_CONTENT_WATCH,
        ]);

        $content->markPending($hash);

        return $content;
    }

    public function check(ArticleContent $content): ArticleContent
    {
        $translation = $content->translation;

        if (! $translation instanceof ArticleTranslation) {
            return $this->markFailed($content, 'Article translation not found.');
        }

        $text = $this->textExtractor->extract($translation);

        if ($text === null) {
            return $this->markFailed($content, 'Article translation content is empty.');
        }

        $content->forceFill([
            'status' => ArticleContent::STATUS_CHECKING,
            'error_message' => null,
        ])->save();

        try {
            $response = $this->client->check($text);
        } catch (\Throwable $exception) {
            return $this->markFailed($content, $exception->getMessage());
        }

        $content->forceFill([
            'status' => ArticleContent::STATUS_CHECKED,
            'uniqueness_percent' => (float) $response['percent'],
            'content_hash' => $this->textExtractor->hash($text),
            'response' => $response,
            'error_message' => null,
            'checked_at' => Carbon::now(),
        ])->save();

        return $content;
    }

    private function markFailed(ArticleContent $content, string $message): ArticleContent
    {
        $content->forceFill([
            'status' => ArticleContent::STATUS_FAILED,
            'uniqueness_percent' => 0,
            'response' => null,
            'error_message' => $message,
            'checked_at' => Carbon::now(),
        ])->save();

        return $content;
    }

    private function shouldCheckLocale(?string $locale): bool
    {
        return is_string($locale)
            && in_array($locale, config('article_content.enabled_locales', ['uk', 'ru']), true);
    }
}
