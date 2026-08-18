<?php

namespace App\Services\Translation;

use App\Services\Translation\Contracts\TranslationProvider;
use InvalidArgumentException;

final readonly class EditorJsTranslationService
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

    public function __construct(
        private TranslationProvider $provider,
    ) {}

    public function countTranslatableCharacters(?string $content): int
    {
        if ($content === null || trim($content) === '') {
            return 0;
        }

        $data = $this->decode($content);
        $segments = $this->collectSegments($data);

        return array_sum(array_map(
            static fn (array $segment): int => mb_strlen($segment['text']),
            $segments
        ));
    }

    public function translate(?string $content, string $sourceLocale, string $targetLocale): ?string
    {
        if ($content === null || trim($content) === '') {
            return $content;
        }

        $data = $this->decode($content);
        $segments = $this->collectSegments($data);

        if ($segments === []) {
            return $content;
        }

        $translated = $this->provider->translateMany(
            array_column($segments, 'text'),
            $sourceLocale,
            $targetLocale,
            'html'
        );

        foreach ($segments as $index => $segment) {
            $this->setPath($data, $segment['path'], $translated[$index] ?? $segment['text']);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        $data = json_decode($content, true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('EditorJs content must be a valid JSON object.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array{path: list<string|int>, text: string}>
     */
    private function collectSegments(array $data): array
    {
        $segments = [];

        foreach (($data['blocks'] ?? []) as $blockIndex => $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            if (! is_string($type)) {
                continue;
            }

            foreach (self::TEXT_PATHS_BY_BLOCK_TYPE[$type] ?? [] as $path) {
                $fullPath = array_merge(['blocks', $blockIndex], $path);
                $text = $this->getPath($data, $fullPath);

                if (is_string($text) && trim(strip_tags($text)) !== '') {
                    $segments[] = [
                        'path' => $fullPath,
                        'text' => $text,
                    ];
                }
            }

            if ($type === 'list') {
                $this->collectListSegments($data, ['blocks', $blockIndex, 'data', 'items'], $segments);
            }

            if ($type === 'checklist') {
                $this->collectChecklistSegments($data, ['blocks', $blockIndex, 'data', 'items'], $segments);
            }

            if ($type === 'table') {
                $this->collectTableSegments($data, ['blocks', $blockIndex, 'data', 'content'], $segments);
            }
        }

        return $segments;
    }

    /**
     * @param list<string|int> $basePath
     * @param list<array{path: list<string|int>, text: string}> $segments
     */
    private function collectListSegments(array $data, array $basePath, array &$segments): void
    {
        $items = $this->getPath($data, $basePath);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (is_string($item) && trim(strip_tags($item)) !== '') {
                $segments[] = [
                    'path' => array_merge($basePath, [$index]),
                    'text' => $item,
                ];
            }
        }
    }

    /**
     * @param list<string|int> $basePath
     * @param list<array{path: list<string|int>, text: string}> $segments
     */
    private function collectChecklistSegments(array $data, array $basePath, array &$segments): void
    {
        $items = $this->getPath($data, $basePath);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $textPath = array_merge($basePath, [$index, 'text']);
            $text = $this->getPath($data, $textPath);

            if (is_string($text) && trim(strip_tags($text)) !== '') {
                $segments[] = [
                    'path' => $textPath,
                    'text' => $text,
                ];
            }
        }
    }

    /**
     * @param list<string|int> $basePath
     * @param list<array{path: list<string|int>, text: string}> $segments
     */
    private function collectTableSegments(array $data, array $basePath, array &$segments): void
    {
        $rows = $this->getPath($data, $basePath);

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $rowIndex => $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach ($row as $cellIndex => $cell) {
                if (is_string($cell) && trim(strip_tags($cell)) !== '') {
                    $segments[] = [
                        'path' => array_merge($basePath, [$rowIndex, $cellIndex]),
                        'text' => $cell,
                    ];
                }
            }
        }
    }

    /**
     * @param list<string|int> $path
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

    /**
     * @param array<string, mixed> $data
     * @param list<string|int> $path
     */
    private function setPath(array &$data, array $path, string $value): void
    {
        $target = &$data;
        $last = array_pop($path);

        foreach ($path as $part) {
            $target = &$target[$part];
        }

        $target[$last] = $value;
    }
}
