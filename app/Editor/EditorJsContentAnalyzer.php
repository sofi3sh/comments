<?php

namespace App\Editor;


final class EditorJsContentAnalyzer
{
    /**
     * Повертає кількість символів у текстовому вмісті EditorJS.
     */
    public function textLength(?string $content): int
    {
        return mb_strlen($this->text($content));
    }

    /**
     * Витягує та нормалізує текст із JSON-структури EditorJS.
     */
    public function text(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return $this->normalizeText($content);
        }

        $blocks = $decoded['blocks'] ?? [];

        if (! is_array($blocks)) {
            return '';
        }

        $pieces = [];
        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            array_push($pieces, ...$this->blockTextPieces($block));
        }

        $text = $this->normalizeText(implode(' ', $pieces));

        return $text;
    }
    /**
     * Check if EditorJS content is effectively empty.
     */
    public function isEmpty(?string $content): bool
    {
        if (empty($content)) {
            return true;
        }

        $decoded = json_decode($content, true);

        if (
            json_last_error() !== JSON_ERROR_NONE
            || !is_array($decoded)
        ) {
            return true;
        }

        $blocks = $decoded['blocks'] ?? null;

        if (
            !is_array($blocks)
            || empty($blocks)
        ) {
            return true;
        }

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            if ($this->isMeaningfulBlock($block)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether block contains meaningful content.
     */
    private function isMeaningfulBlock(array $block): bool
    {
        $type = $block['type'] ?? null;
        $data = $block['data'] ?? [];

        if (!is_array($data)) {
            return false;
        }

        if (in_array($type, [
            'paragraph',
            'header',
            'quote',
        ], true)) {

            return $this->hasText(
                $data['text'] ?? null
            );
        }

        if ($type === 'code') {

            return $this->hasText(
                $data['code'] ?? null
            );
        }

        if ($type === 'warning') {

            return
                $this->hasText($data['title'] ?? null)
                || $this->hasText($data['message'] ?? null);
        }

        if (in_array($type, [
            'gallery',
            'embed',
        ], true)) {

            return
                !empty($data['attachment_id'])
                || !empty($data['url'])
                || ($type === 'gallery' && $this->hasGalleryImage($data['images'] ?? null));
        }

        return false;
    }

    private function hasGalleryImage(mixed $images): bool
    {
        if (! is_array($images)) {
            return false;
        }

        foreach ($images as $image) {
            if (is_array($image) && ! empty($image['attachment_id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check normalized text value.
     */
    private function hasText(?string $text): bool
    {
        if ($text === null) {
            return false;
        }

        return $this->normalizeText($text) !== '';
    }

    /**
     * Повертає текстові поля, що належать конкретному типу блока EditorJS.
     *
     * @param array<string, mixed> $block
     * @return list<string>
     */
    private function blockTextPieces(array $block): array
    {
        $type = $block['type'] ?? null;
        $data = $block['data'] ?? [];

        if (! is_string($type) || ! is_array($data)) {
            return [];
        }

        $fields = match ($type) {
            'paragraph', 'header', 'quote' => ['text'],
            'code' => ['code'],
            'warning' => ['title', 'message'],
            default => [],
        };

        return array_values(array_filter(
            array_map(
                static fn (string $field): mixed => $data[$field] ?? null,
                $fields,
            ),
            'is_string',
        ));
    }

    /**
     * Видаляє HTML і нормалізує пробіли у тексті.
     */
    private function normalizeText(string $text): string
    {
        return trim((string) preg_replace(
            '/\s+/u',
            ' ',
            html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ));
    }
}
