<?php

namespace App\SEO\Pages;

use App\SEO\Data\SeoPage;

final class DossierSeo extends AbstractSeo
{
    public static function make(): self
    {
        return new self();
    }

    public function toSeoPage(): SeoPage
    {
        return new SeoPage(
            title: __('page.dossier.title') . __('app-meta.title_short'),
            canonicalUrl: $this->resolveUrl(app()->getLocale()),
            alternateUrls: $this->urlsForAvailableLocales(
                fn (string $locale): string => $this->resolveUrl($locale)
            ),
            imageUrl: asset(config('app.default_cover')), // todo
        );
    }

    private function resolveUrl(string $locale): string
    {
        return route('locale.dossier', ['locale' => $locale]);
    }
}
