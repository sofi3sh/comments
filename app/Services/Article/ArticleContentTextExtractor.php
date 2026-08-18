<?php

namespace App\Services\Article;

use App\Models\Articles\Translate\ArticleTranslation;

final class ArticleContentTextExtractor
{
    private const TEXT_PATHS_BY_BLOCK_TYPE = [
        'paragraph' => [
            ['data', 'text'],
        ],
        'header' => [
            ['data', 'text'],
        ],
        'quote' => [
            ['data', 'text'],
            ['data', 'caption'],
        ],
        'warning' => [
            ['data', 'title'],
            ['data', 'message'],
        ],
        'gallery' => [
            ['data', 'alt'],
            ['data', 'title'],
            ['data', 'caption'],
        ],
    ];

    public function extract(ArticleTranslation $translation): ?string
    {
        $text = $this->extractFromEditorJs($translation->content);

        if ($text === null && is_string($translation->content_html)) {
            $text = $translation->content_html;
        }

        if ($text === null) {
            return null;
        }

        $normalized = $this->normalize($text);

        return $normalized !== '' ? $normalized : null;
    }

    public function hash(string $text): string
    {
        return hash('sha256', $this->normalize($text));
    }

    private function extractFromEditorJs(?string $content): ?string
    {
        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        $data = json_decode($content, true);

        if (! is_array($data)) {
            return null;
        }

        $segments = [];

        foreach (($data['blocks'] ?? []) as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            if (! is_string($type)) {
                continue;
            }

            foreach (self::TEXT_PATHS_BY_BLOCK_TYPE[$type] ?? [] as $path) {
                $value = $this->getPath($block, $path);

                if (is_string($value)) {
                    $segments[] = $value;
                }
            }
        }

        return $segments !== [] ? implode("\n", $segments) : null;
    }


    /**
     * @param list<string> $path
     */
    private function getPath(array $data, array $path): mixed
    {
        $value = $data;

        foreach ($path as $part) {
            if (! is_array($value) || ! array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

    private function normalize(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }
}
