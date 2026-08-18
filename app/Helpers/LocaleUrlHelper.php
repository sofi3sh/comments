<?php

namespace App\Helpers;

use App\Models\Settings\Locale;
use Illuminate\Support\Facades\Request;

class LocaleUrlHelper
{
    /**
     * Get path without locale prefix (e.g. "ru/category" -> "category").
     */
    public static function pathWithoutLocalePrefix(): string
    {
        $path = Request::path();
        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);
        $first = $segments[0] ?? '';

        if (static::isLocalePrefix($first)) {
            return implode('/', array_slice($segments, 1)) ?: '';
        }

        return $path;
    }

    /**
     * Build full URL for the given locale (uses current request path).
     * Default locale (Ukrainian) has no prefix; ru/en use /ru/ and /en/.
     */
    public static function localizedUrl(string $locale, ?string $pathWithoutPrefix = null): string
    {
        $path = trim($pathWithoutPrefix ?? static::pathWithoutLocalePrefix(), '/');

        if ($path === '') {
            return static::localizedHomepageUrl($locale);
        }

        return url($locale . '/' . $path);
    }

    /**
     * Build homepage URL for the given locale.
     * The main site's default locale uses the domain root; category domains
     * always keep the locale prefix, including Ukrainian.
     */
    public static function localizedHomepageUrl(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $defaultCode = Locale::getDefault()?->code ?? 'uk';

        $mainDomain = parse_url((string) config('app.url'), PHP_URL_HOST);
        $currentDomain = Request::getHost();

        if ($mainDomain && $currentDomain !== $mainDomain) {
            return route('category.homepage', [
                'domain' => $currentDomain,
                'locale' => $locale,
            ]);
        }

        return $locale === $defaultCode
            ? url('/')
            : url($locale);
    }

    /**
     * Check if the segment is a valid locale prefix (non-default locale in URL).
     */
    public static function isLocalePrefix(string $segment): bool
    {
        return in_array($segment, Locale::getAvailableAsArr('code'), true);
    }
}
