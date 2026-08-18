<?php

namespace App\SEO\Data;

/**
 * Page-specific SEO values. Everything derivable (og:/twitter: mirrors,
 * site-wide defaults, fallback image) is filled in by SeoManager at render time.
 */
final class SeoPage
{
    /**
     * @param array<string, string> $alternateUrls
     * @param array<int, string> $articleTags
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $keywords = null,
        public readonly ?string $canonicalUrl = null,
        public readonly array $alternateUrls = [],
        public readonly ?string $robots = null,
        public readonly string $ogType = 'website',
        public readonly ?string $imageUrl = null,
        public readonly ?int $imageWidth = null,
        public readonly ?int $imageHeight = null,
        public readonly ?string $ampUrl = null,
        public readonly ?string $articleSection = null,
        public readonly ?string $articlePublishedTime = null,
        public readonly ?string $articleModifiedTime = null,
        public readonly ?string $articleAuthor = null,
        public readonly array $articleTags = [],
    ) {
    }
}
