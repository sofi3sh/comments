<?php

namespace App\SEO\Data;

final class SeoData
{
    /**
     * @param array<string, string> $alternateUrls
     * @param array<int, string> $articleTags
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $keywords = null,
        public readonly ?string $newsKeywords = null,
        public readonly ?string $canonicalUrl = null,
        public readonly array $alternateUrls = [],
        public readonly ?string $robots = null,
        public readonly ?string $themeColor = null,
        public readonly ?string $ogSiteName = null,
        public readonly ?string $ogUrl = null,
        public readonly ?string $ogLocale = null,
        public readonly ?string $ogType = null,
        public readonly ?string $ogTitle = null,
        public readonly ?string $ogDescription = null,
        public readonly ?string $ogImage = null,
        public readonly ?string $ogImageSecureUrl = null,
        public readonly ?int $ogImageWidth = null,
        public readonly ?int $ogImageHeight = null,
        public readonly ?string $twitterCard = null,
        public readonly ?string $twitterTitle = null,
        public readonly ?string $twitterDescription = null,
        public readonly ?string $twitterSite = null,
        public readonly ?string $twitterImage = null,
        public readonly ?string $twitterCreator = null,
        public readonly ?string $articleSection = null,
        public readonly ?string $articlePublishedTime = null,
        public readonly ?string $articleModifiedTime = null,
        public readonly ?string $articleAuthor = null,
        public readonly array $articleTags = [],
        public readonly ?string $ampUrl = null,
        public readonly ?string $author = null,
        public readonly ?string $copyright = null,
        public readonly ?string $publisher = null,
    ) {
    }
}
