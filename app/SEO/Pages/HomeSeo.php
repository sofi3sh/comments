<?php

namespace App\SEO\Pages;

use App\SEO\Data\SeoPage;

final class HomeSeo extends AbstractSeo
{
    public static function make(): self
    {
        return new self();
    }

    public function toSeoPage(): SeoPage
    {
        return new SeoPage(
            title: __('app-meta.title'),
            description: __('app-meta.description'),
            keywords: __('app-meta.homepage.keywords'),
            canonicalUrl: $this->resolveCanonicalUrl(),
            alternateUrls: [
                'uk' => route('homepage'),
                'en' => route('locale.homepage', ['locale' => 'en']),
                'ru' => route('locale.homepage', ['locale' => 'ru']),
            ],
            imageUrl: asset(config('app.default_cover')), // todo
        );
    }

    private function resolveCanonicalUrl(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'uk') {
            return route('homepage');
        }

        return route('locale.homepage', ['locale' => $locale]);
    }
}
