<?php

namespace App\Services\Translation;

use App\Services\Translation\Contracts\TranslationProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class GoogleTranslationProvider implements TranslationProvider
{
    public function translateMany(array $texts, string $sourceLocale, string $targetLocale, string $format = 'text'): array
    {
        $texts = array_values($texts);

        if ($texts === []) {
            return [];
        }

        $key = config('article_translation.providers.google.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Google Translate API key is not configured.');
        }

        $response = Http::timeout(30)
            ->post(config('article_translation.providers.google.endpoint').'?key='.urlencode($key), [
                'q' => $texts,
                'source' => $sourceLocale,
                'target' => $targetLocale,
                'format' => $format,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Google Translate request failed.');
        }

        $translations = $response->json('data.translations');

        if (! is_array($translations)) {
            throw new RuntimeException('Google Translate returned an unexpected response.');
        }

        return array_map(
            static fn (array $translation): string => (string) ($translation['translatedText'] ?? ''),
            $translations
        );
    }
}
