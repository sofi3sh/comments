<?php

namespace App\SEO\Pages;

use App\Models\Articles\ArticleType;
use App\SEO\Data\SeoPage;

final class ArticleTypeSeo extends AbstractSeo
{
    public function __construct(private readonly ArticleType $type)
    {
    }

    public static function make(ArticleType $type): self
    {
        return new self($type);
    }

    public function toSeoPage(): SeoPage
    {
        $type = $this->type;

        return new SeoPage(
            title: $this->resolveTitle($type),
            description: $this->resolveDescription($type),
            canonicalUrl: $this->resolveTypeUrl($type, app()->getLocale()),
            alternateUrls: $this->urlsForAvailableLocales(
                fn (string $locale): string => $this->resolveTypeUrl($type, $locale)
            ),
        );
    }

    private function resolveTitle(ArticleType $type): string
    {
        $typeName = $this->resolveTypeName($type);

        return match ($type->code) {
            ArticleType::NEWS => __('app-meta.title_news'),
            ArticleType::ARTICLE => $typeName . __('app-meta.title_common'),
            ArticleType::INTERVIEW => $typeName . __('app-meta.title_common'),
            ArticleType::OPINION => $typeName . __('app-meta.title_short'),
        };
    }

    private function resolveDescription(ArticleType $type): string
    {
        $typeName = $this->resolveTypeName($type);

        return match ($type->code) {
            ArticleType::OPINION => __('app-meta.description_opinion'),
            default => $typeName . __('app-meta.description_type')
        };
    }

    private function resolveTypeUrl(ArticleType $type, string $locale): string
    {
        return route('locale.type.show.list', [
            'locale' => $locale,
            'type' => ArticleType::codeForRoute((string) $type->code),
        ]);
    }

    private function resolveTypeName(ArticleType $type): string
    {
        return $type->name ?: (string) $type->code;
    }
}
