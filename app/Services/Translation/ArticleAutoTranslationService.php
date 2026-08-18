<?php

namespace App\Services\Translation;

use App\Services\Translation\Contracts\TranslationProvider;
use Illuminate\Validation\ValidationException;

final readonly class ArticleAutoTranslationService
{
    public function __construct(
        private TranslationProvider $provider,
        private EditorJsTranslationService $editorJsTranslationService,
    ) {}

    /**
     * @param array{title?: string|null, excerpt?: string|null, content?: string|null} $source
     * @param array{title?: string|null, excerpt?: string|null, content?: string|null} $target
     * @return array{fields: array<string, string|null>, skipped: list<string>}
     */
    public function translate(
        array $source,
        array $target,
        string $sourceLocale,
        string $targetLocale,
        bool $overwrite
    ): array {
        $fields = [];
        $skipped = [];

        $textFields = [];
        foreach (['title', 'excerpt'] as $field) {
            if (! $overwrite && $this->filled($target[$field] ?? null)) {
                $skipped[] = $field;
                continue;
            }

            $value = $source[$field] ?? null;
            if (! $this->filled($value)) {
                continue;
            }

            $textFields[$field] = (string) $value;
        }

        $content = $source['content'] ?? null;
        if (! $overwrite && $this->contentFilled($target['content'] ?? null)) {
            $skipped[] = 'content';
        } elseif ($this->contentFilled($content)) {
            $fields['content'] = $this->editorJsTranslationService->translate(
                (string) $content,
                $sourceLocale,
                $targetLocale
            );
        }

        if ($textFields !== []) {
            $translated = $this->provider->translateMany(
                array_values($textFields),
                $sourceLocale,
                $targetLocale,
                'text'
            );

            foreach (array_keys($textFields) as $index => $field) {
                $fields[$field] = $translated[$index] ?? $textFields[$field];
            }
        }

        return [
            'fields' => $fields,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param array{title?: string|null, excerpt?: string|null, content?: string|null} $source
     */
    public function ensureWithinSyncLimit(array $source): void
    {
        $characters = mb_strlen((string) ($source['title'] ?? ''))
            + mb_strlen((string) ($source['excerpt'] ?? ''))
            + $this->editorJsTranslationService->countTranslatableCharacters($source['content'] ?? null);

        $limit = (int) config('article_translation.max_sync_characters', 30000);

        if ($characters > $limit) {
            throw ValidationException::withMessages([
                'content' => __('article-translate.auto_translate.content_too_large', [
                    'limit' => $limit,
                ]),
            ]);
        }
    }

    private function filled(mixed $value): bool
    {
        return is_string($value)
            ? trim($value) !== ''
            : $value !== null;
    }

    private function contentFilled(mixed $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            return $this->editorJsTranslationService->countTranslatableCharacters($value) > 0;
        } catch (\Throwable) {
            return trim($value) !== '';
        }
    }
}
