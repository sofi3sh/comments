<?php

namespace App\SEO\Pages;

use App\Models\Articles\Tag;
use App\SEO\Data\SeoPage;
use Illuminate\Support\Str;

final class TagSeo extends AbstractSeo
{
    public function __construct(private readonly Tag $tag)
    {
    }

    public static function make(Tag $tag): self
    {
        return new self($tag);
    }

    public function toSeoPage(): SeoPage
    {
        $tag = $this->tag;
        $seoTranslation = $tag->seoMeta?->translate(app()->getLocale());

        return new SeoPage(
            title: $this->resolveTitle($tag, $seoTranslation?->meta_title),
            description: $this->resolveDescription($tag, $seoTranslation?->meta_description),
            keywords: $this->resolveKeywords($tag, $seoTranslation?->meta_keywords),
            canonicalUrl: $this->resolveTagUrl($tag, app()->getLocale()),
            alternateUrls: $this->urlsForTranslationLocales(
                $tag,
                fn (string $locale): string => $this->resolveTagUrl($tag, $locale)
            ),
        );
    }

    private function resolveTitle(Tag $tag, ?string $seoTitle): string
    {
        if ($this->filled($seoTitle)) {
            return trim((string) $seoTitle);
        }

        return $tag->title;
    }

    private function resolveDescription(Tag $tag, ?string $seoDescription): string
    {
        if ($this->filled($seoDescription)) {
            return trim((string) $seoDescription);
        }

        if ($this->filled($tag->description)) {
            return Str::limit(trim((string) $tag->description), 300, '');
        }

        return ($tag->title ?: (string) $tag->slug) . __('app-meta.description_type');
    }

    private function resolveKeywords(Tag $tag, ?string $seoKeywords): ?string
    {
        if ($this->filled($seoKeywords)) {
            return trim((string) $seoKeywords);
        }

        return $tag->keywords ?: null;
    }

    private function resolveTagUrl(Tag $tag, string $locale): string
    {
        return route('locale.tag.show', [
            'locale' => $locale,
            'slug'   => $tag->slug,
        ]);
    }
}
