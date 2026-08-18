<?php

namespace App\Services\Article;

use App\Models\Articles\ArticleContent;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class ContentWatchClient
{
    /**
     * @return array<string, mixed>
     */
    public function check(string $text): array
    {
        $key = config('article_content.providers.content_watch.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Content Watch API key is not configured.');
        }

        $response = Http::asForm()
            ->timeout(60)
            ->post(config('article_content.providers.content_watch.endpoint'), [
                'key' => $key,
                'text' => $text,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Content Watch request failed.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Content Watch returned an unexpected response.');
        }

        if (($data['error'] ?? null) !== null) {
            throw new RuntimeException((string) $data['error']);
        }

        if (! array_key_exists('percent', $data)) {
            throw new RuntimeException('Content Watch response does not contain uniqueness percent.');
        }

        $data['provider'] = ArticleContent::PROVIDER_CONTENT_WATCH;

        return $data;
    }
}
