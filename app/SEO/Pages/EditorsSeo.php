<?php

namespace App\SEO\Pages;

use App\SEO\Data\SeoPage;

final class EditorsSeo extends AbstractSeo
{
    public static function make(): self
    {
        return new self();
    }

    public function toSeoPage(): SeoPage
    {
        return new SeoPage(
            title: __('page.editors.title') . __('app-meta.title_short'),
            description: __('page.editors.description'),
            keywords: __('app-meta.common.keywords'),
            canonicalUrl: $this->resolveUrl(app()->getLocale()),
            alternateUrls: $this->urlsForAvailableLocales(
                fn (string $locale): string => $this->resolveUrl($locale)
            ),
        );
    }

    private function resolveUrl(string $locale): string
    {
        return route('locale.editors', ['locale' => $locale]);
    }
}
