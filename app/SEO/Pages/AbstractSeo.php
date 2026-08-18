<?php

namespace App\SEO\Pages;

use App\Models\Settings\Locale;
use App\SEO\Contracts\SeoSource;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractSeo implements SeoSource
{
    /**
     * @param callable(string): string $urlFor
     * @return array<string, string>
     */
    protected function urlsForAvailableLocales(callable $urlFor): array
    {
        $urls = [];

        foreach (Locale::getAvailableAsArr('code') as $locale) {
            $urls[$locale] = $urlFor($locale);
        }

        return $urls;
    }

    /**
     * @param callable(string): string $urlFor
     * @return array<string, string>
     */
    protected function urlsForTranslationLocales(Model $model, callable $urlFor): array
    {
        $locales = $model->relationLoaded('translations')
            ? $model->translations->pluck('locale')
            : $model->translations()->pluck('locale');

        return $locales
            ->filter(fn ($locale) => is_string($locale) && trim($locale) !== '')
            ->unique()
            ->mapWithKeys(fn (string $locale) => [$locale => $urlFor($locale)])
            ->all();
    }

    /**
     * @param array<int, string|null> $values
     */
    protected function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if ($this->filled($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    protected function filled(?string $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
