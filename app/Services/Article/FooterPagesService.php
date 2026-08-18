<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\Settings\Locale;
use App\Support\PageRoles;
use Illuminate\Support\Facades\Cache;

class FooterPagesService
{
    public function getForLocale(string $locale): array
    {
        return Cache::rememberForever($this->cacheKey($locale), fn() => $this->buildForLocale($locale));
    }

    public function forget(): void
    {
        $locales = Locale::getAvailableAsArr();

        foreach ($locales as $locale) {
            Cache::forget($this->cacheKey($locale));
        }
    }

    private function buildForLocale(string $locale): array
    {
        $pageTypeId = ArticleType::getTypeId(ArticleType::PAGE);

        if ($pageTypeId === null) {
            return [];
        }

        $rolesOrder = array_flip(PageRoles::all());

        return Article::query()
            ->published()
            ->where('type_id', $pageTypeId)
            ->whereHas('meta', fn($query) => $query
                ->where('field', 'page_role')
                ->whereIn('value', PageRoles::all()))
            ->with([
                'meta' => fn($query) => $query->where('field', 'page_role'),
                'translations' => fn($query) => $query->whereIn('locale', [$locale, 'uk']),
            ])
            ->get()
            ->map(function (Article $article) use ($locale) {
                $role = $article->meta->first()?->value;

                if (!is_string($role) || $role === '') {
                    return null;
                }

                $translation = $article->translations->firstWhere('locale', $locale)
                    ?? $article->translations->firstWhere('locale', 'uk');

                $title = $translation?->title;

                if (!is_string($title) || trim($title) === '') {
                    return null;
                }

                return [
                    'role' => $role,
                    'title' => $title,
                    'last_modified' => ($article->updated_at ?? $article->created_at)?->getTimestamp(),
                    'url' => route('locale.page.role', [
                        'domain' => request()->getHost() ?: 'localhost',
                        'locale' => $locale,
                        'role' => $role,
                    ], false),
                ];
            })
            ->filter()
            ->unique('role')
            ->sortBy(fn(array $page) => $rolesOrder[$page['role']] ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    private function cacheKey(string $locale): string
    {
        return "footer_service_pages:{$locale}";
    }
}
