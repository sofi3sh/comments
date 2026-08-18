<?php

namespace App\SEO\Pages;

use App\SEO\Data\SeoPage;

final class DossierTypeSeo extends AbstractSeo
{
    public function __construct(private readonly string $type)
    {
    }

    public static function make(string $type): self
    {
        return new self($type);
    }

    public function toSeoPage(): SeoPage
    {
        return new SeoPage(
            title: $this->resolveTitle(),
            canonicalUrl: $this->resolveUrl(app()->getLocale()),
            alternateUrls: $this->urlsForAvailableLocales(
                fn (string $locale): string => $this->resolveUrl($locale)
            ),
            imageUrl: asset(config('app.default_cover')),
        );
    }

    private function resolveTitle(): string
    {
        $typeTitle = match ($this->type) {
            'persons' => __('page.significant.persons'),
            'company' => __('page.significant.company'),
        };

        return $typeTitle . __('app-meta.title_short');
    }

    private function resolveUrl(string $locale): string
    {
        return route('locale.significant', [
            'locale' => $locale,
            'type'   => $this->type,
        ]);
    }
}
