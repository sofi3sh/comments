<?php

namespace App\Services\Article\Audit;

use App\Editor\EditorContentProcessor;
use App\Editor\EditorService;
use App\Editor\HtmlToEditorJsContentConverter;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleActivityLog;
use App\Models\Articles\Translate\ArticleTranslation;

class ArticleContentActivityLogger
{
    public function __construct(
        private readonly HtmlToEditorJsContentConverter $htmlToEditorJsContentConverter,
        private readonly EditorContentProcessor $editorContentProcessor,
    ) {}

    /**
     * Визначає, які поля контенту з форми відрізняються від поточних перекладів статті.
     * Результат групується за локаллю і має бути отриманий до збереження перекладів.
     *
     * @param array<string, array<string, mixed>> $translations
     * @return array<string, list<string>>
     */
    public function changedFieldsByLocale(Article $article, array $translations): array
    {
        if (empty($translations)) {
            return [];
        }

        $locales = array_keys($translations);

        $existingTranslations = ArticleTranslation::query()
            ->where('article_id', $article->id)
            ->whereIn('locale', $locales)
            ->get()
            ->keyBy('locale');

        $changedFieldsByLocale = [];

        foreach ($translations as $locale => $translation) {
            $existingTranslation = $existingTranslations->get($locale);

            foreach (['content', 'content_html'] as $field) {
                if (!array_key_exists($field, $translation)) {
                    continue;
                }

                $oldValue = $this->oldContentValue($article, $existingTranslation, $field);
                $newValue = $translation[$field];

                if ($this->contentValueChanged($oldValue, $newValue)) {
                    $changedFieldsByLocale[(string) $locale][] = $field;
                }
            }
        }

        return $changedFieldsByLocale;
    }

    /**
     * Повертає старе значення контенту у форматі, придатному для порівняння.
     * У старих записах може бути лише HTML-контент, тому перед порівнянням він конвертується в Editor.js JSON.
     */
    private function oldContentValue(Article $article, ?ArticleTranslation $translation, string $field): mixed
    {
        if (!$translation) {
            return null;
        }

        $oldValue = $translation->{$field};

        if ($field !== 'content' || !$this->isBlankValue($oldValue) || $this->isBlankValue($translation->content_html)) {
            return $oldValue;
        }

        $converted = $this->htmlToEditorJsContentConverter->convert($translation->content_html);

        return $this->editorContentProcessor->process($converted, [
            'do_follow' => (bool) $article->do_follow,
            'target_blank' => true,
            'operation' => EditorService::SAVE_ACTION,
        ]);
    }

    /**
     * Записує по одному легкому activity-логу на кожну змінену локаль без збереження тіла статті.
     *
     * @param array<string, list<string>> $changedFieldsByLocale
     */
    public function recordUpdated(Article $article, array $changedFieldsByLocale): void
    {
        if (empty($changedFieldsByLocale)) {
            return;
        }

        $user = backpack_user();
        $request = request();

        foreach ($changedFieldsByLocale as $locale => $fields) {
            ArticleActivityLog::query()->create([
                'article_id' => $article->id,
                'user_id' => $user?->getKey(),
                'event' => ArticleActivityLog::EVENT_CONTENT_UPDATED,
                'locale' => $locale,
                'ip_address' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'fields' => array_values(array_unique($fields)),
                ],
            ]);
        }
    }

    /**
     * Порівнює контент після нормалізації JSON редактора, щоб службові timestamp і порядок ключів не створювали шум.
     */
    private function contentValueChanged(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $this->normalizeContentValueForAudit($oldValue);
        $newValue = $this->normalizeContentValueForAudit($newValue);

        return $oldValue !== $newValue;
    }

    /**
     * Перетворює scalar, array і JSON-значення контенту на стабільний рядок для порівняння в аудиті.
     */
    private function normalizeContentValueForAudit(mixed $value): string
    {
        if ($this->isBlankValue($value)) {
            return '';
        }

        if (is_array($value)) {
            return $this->encodeNormalizedContentArray($value);
        }

        if (!is_string($value)) {
            return (string) $value;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            unset($decoded['time']);

            return $this->encodeNormalizedContentArray($decoded);
        }

        return trim($value);
    }

    private function isBlankValue(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * Кодує масиви контенту з відсортованими асоціативними ключами для детермінованого порівняння.
     */
    private function encodeNormalizedContentArray(array $value): string
    {
        $this->sortContentArrayKeys($value);

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function sortContentArrayKeys(array &$value): void
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->sortContentArrayKeys($item);
            }
        }
        unset($item);

        if (!array_is_list($value)) {
            ksort($value);
        }
    }
}
