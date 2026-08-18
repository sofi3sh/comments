<?php

namespace App\SEO\Pages;

use App\Models\Articles\Article;
use App\Models\Articles\Translate\ArticleTranslation;
use App\SEO\Data\SeoPage;
use App\Services\Article\ArticleContentTextExtractor;
use Illuminate\Support\Str;

final class PageSeo extends AbstractSeo
{
    public function __construct(private readonly Article $page)
    {
    }

    public static function make(Article $page): self
    {
        return new self($page);
    }

    public function toSeoPage(): SeoPage
    {
        $page = $this->page;
        $locale = app()->getLocale();
        $translation = $page->translate($locale);
        $seoTranslation = $page->seoMeta?->translate($locale);

        return new SeoPage(
            title: $this->resolveTitle($page, $seoTranslation?->meta_title),
            description: $this->resolveDescription($translation, $seoTranslation?->meta_description),
            canonicalUrl: $page->getArticleUrl(),
            alternateUrls: $this->resolveAlternateUrls($page),
            imageUrl: $page->getCoverUrl(),
        );
    }

    private function resolveTitle(Article $page, ?string $seoTitle): ?string
    {
        return $this->firstNonEmpty([
            $seoTitle,
            $page->title_with_markers,
            $page->title,
        ]);
    }

    private function resolveDescription(?ArticleTranslation $translation, ?string $seoDescription): ?string
    {
        $contentText = $translation !== null
            ? app(ArticleContentTextExtractor::class)->extract($translation)
            : null;

        return $this->firstNonEmpty([
            $seoDescription,
            $translation?->excerpt,
            $contentText !== null ? Str::limit($contentText, 300, '') : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function resolveAlternateUrls(Article $page): array
    {
        $alternateUrls = [];

        foreach ($page->getAvailableLocales() as $locale) {
            $code = $locale->code ?? null;

            if (! is_string($code) || $code === '') {
                continue;
            }

            $alternateUrls[$code] = $page->getArticleUrlForLocale($code);
        }

        return $alternateUrls;
    }
}
