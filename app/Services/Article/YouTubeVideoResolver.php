<?php

namespace App\Services\Article;

final class YouTubeVideoResolver
{
    /**
     * Витягує ідентифікатор відео з підтримуваного YouTube URL або приймає готовий ID.
     */
    public static function idFromUrlOrId(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (self::isValidId($value)) {
            return $value;
        }

        $parts = parse_url($value);

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $videoId = null;

        if (in_array($host, config('services.youtube.short_hosts', []), true)) {
            $videoId = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, config('services.youtube.hosts', []), true)) {
            if ($path === 'watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $videoId = $query['v'] ?? null;
            } elseif (preg_match('#^(?:embed|shorts|live)/([^/]+)#', $path, $matches)) {
                $videoId = $matches[1];
            }
        }

        return is_string($videoId) && self::isValidId($videoId) ? $videoId : null;
    }

    /**
     * Формує канонічне посилання на сторінку відео в YouTube.
     */
    public static function videoUrl(?string $youtubeId): ?string
    {
        return self::urlFromTemplate('watch_url', $youtubeId);
    }

    /**
     * Формує privacy-friendly URL для вставки YouTube-плеєра з автовідтворенням.
     */
    public static function embedUrl(?string $youtubeId): ?string
    {
        return self::urlFromTemplate('embed_url', $youtubeId);
    }

    /**
     * Формує URL максимальної доступної заставки YouTube.
     */
    public static function thumbnailUrl(?string $youtubeId): ?string
    {
        return self::urlFromTemplate('thumbnail_url', $youtubeId);
    }

    /**
     * Формує резервний URL заставки для роликів без maxresdefault-зображення.
     */
    public static function thumbnailFallbackUrl(?string $youtubeId): ?string
    {
        return self::urlFromTemplate('thumbnail_fallback_url', $youtubeId);
    }

    /**
     * Формує URL за шаблоном із конфігурації для коректного ідентифікатора відео.
     */
    private static function urlFromTemplate(string $templateName, ?string $youtubeId): ?string
    {
        if (! self::isValidId($youtubeId)) {
            return null;
        }

        $template = config("services.youtube.{$templateName}");

        return is_string($template) ? sprintf($template, $youtubeId) : null;
    }

    /**
     * Перевіряє формат ідентифікатора YouTube-відео.
     */
    private static function isValidId(?string $youtubeId): bool
    {
        return is_string($youtubeId)
            && preg_match('/^[A-Za-z0-9_-]{11}$/', $youtubeId) === 1;
    }
}
