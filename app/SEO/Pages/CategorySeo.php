<?php

namespace App\SEO\Pages;

use App\Models\Articles\Category;
use App\SEO\Data\SeoPage;
use Illuminate\Support\Str;

final class CategorySeo extends AbstractSeo
{
    public function __construct(private readonly Category $category)
    {
    }

    public static function make(Category $category): self
    {
        return new self($category);
    }

    public function toSeoPage(): SeoPage
    {
        $category = $this->category;
        $seoTranslation = $category->seoMeta?->translate(app()->getLocale());

        return new SeoPage(
            title: $this->resolveTitle($category, $seoTranslation?->meta_title),
            description: $this->resolveDescription($category, $seoTranslation?->meta_description),
            keywords: $this->resolveKeywords($category, $seoTranslation?->meta_keywords),
            canonicalUrl: $this->resolveCategoryUrl($category, app()->getLocale()),
            alternateUrls: $this->urlsForTranslationLocales(
                $category,
                fn (string $locale): string => $this->resolveCategoryUrl($category, $locale)
            ),
        );
    }

    private function resolveTitle(Category $category, ?string $seoTitle): string
    {
        if ($this->filled($seoTitle)) {
            return trim((string) $seoTitle);
        }

        return $category->title ?: $category->name ?: (string) $category->slug;
    }

    private function resolveDescription(Category $category, ?string $seoDescription): string
    {
        if ($this->filled($seoDescription)) {
            return trim((string) $seoDescription);
        }

        if ($this->filled($category->description)) {
            return Str::limit(trim((string) $category->description), 300, '');
        }

        return ($category->name ?: (string) $category->slug) . __('app-meta.description_type');
    }

    private function resolveKeywords(Category $category, ?string $seoKeywords): ?string
    {
        if ($this->filled($seoKeywords)) {
            return trim((string) $seoKeywords);
        }

        return $category->keywords ?: null;
    }

    private function resolveCategoryUrl(Category $category, string $locale): ?string
    {
        $site = $category->getSite();

        if (! $site) {
            return null;
        }

        return route('category.homepage', [
            'locale' => $locale,
            'domain' => $site->domain,
        ]);
    }
}
