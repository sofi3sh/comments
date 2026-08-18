<?php

namespace App\SEO;

use App\SEO\Contracts\SeoSource;
use App\SEO\Data\SeoData;
use App\SEO\Data\SeoPage;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Per-request SEO state. Controllers register a SeoSource (and optionally a
 * paginator); the actual SeoData is built lazily when the layout calls data().
 *
 * Bound as scoped() so Octane/FrankenPHP flushes it between requests.
 */
final class SeoManager
{
    private ?SeoSource $source = null;

    private ?LengthAwarePaginator $paginator = null;

    private ?SeoData $data = null;

    public function set(SeoSource $source): self
    {
        $this->source = $source;
        $this->data = null;

        return $this;
    }

    public function paginate(LengthAwarePaginator $paginator): self
    {
        $this->paginator = $paginator;
        $this->data = null;

        return $this;
    }

    public function data(): SeoData
    {
        return $this->data ??= $this->expand($this->applyPagination($this->page()));
    }

    private function page(): SeoPage
    {
        return $this->source?->toSeoPage() ?? new SeoPage(title: __('app-meta.title'));
    }

    /**
     * Expand compact page values into the full meta set: mirror title,
     * description, canonical and image into og:/twitter: fields and fill
     * site-wide defaults.
     */
    private function expand(SeoPage $page): SeoData
    {
        $author = __('app-meta.author');

        $image = $page->imageUrl;
        $imageWidth = $page->imageWidth;
        $imageHeight = $page->imageHeight;

        if ($image === null) {
            $image = asset(config('seo.default_og_image'));
            $imageWidth = 1200;
            $imageHeight = 630;
        }

        return new SeoData(
            title: $page->title,
            description: $page->description,
            keywords: $page->keywords,
            newsKeywords: $page->keywords,
            canonicalUrl: $page->canonicalUrl,
            alternateUrls: $page->alternateUrls,
            robots: $page->robots,
            themeColor: config('seo.theme_color'),
            ogSiteName: $author,
            ogUrl: $page->canonicalUrl,
            ogType: $page->ogType,
            ogTitle: $page->title,
            ogDescription: $page->description,
            ogImage: $image,
            ogImageSecureUrl: $image,
            ogImageWidth: $imageWidth,
            ogImageHeight: $imageHeight,
            twitterCard: 'summary_large_image',
            twitterTitle: $page->title,
            twitterDescription: $page->description,
            twitterSite: config('seo.twitter_site'),
            twitterImage: $image,
            twitterCreator: config('seo.twitter_creator'),
            articleSection: $page->articleSection,
            articlePublishedTime: $page->articlePublishedTime,
            articleModifiedTime: $page->articleModifiedTime,
            articleAuthor: $page->articleAuthor,
            articleTags: $page->articleTags,
            ampUrl: $page->ampUrl,
            author: $author,
            copyright: $author,
            publisher: $author,
        );
    }

    private function applyPagination(SeoPage $page): SeoPage
    {
        $currentPage = $this->paginator?->currentPage() ?? 1;

        if ($currentPage <= 1) {
            return $page;
        }

        return new SeoPage(
            title: $this->paginatedTitle($page->title, $currentPage),
            description: $this->paginatedDescription($page->description, $currentPage),
            keywords: $page->keywords,
            canonicalUrl: $page->canonicalUrl !== null
                ? $this->withPageQuery($page->canonicalUrl, $currentPage)
                : null,
            alternateUrls: array_map(
                fn (string $url): string => $this->withPageQuery($url, $currentPage),
                $page->alternateUrls
            ),
            robots: $page->robots ?? 'index, follow',
            ogType: $page->ogType,
            imageUrl: $page->imageUrl,
            imageWidth: $page->imageWidth,
            imageHeight: $page->imageHeight,
            ampUrl: $page->ampUrl,
            articleSection: $page->articleSection,
            articlePublishedTime: $page->articlePublishedTime,
            articleModifiedTime: $page->articleModifiedTime,
            articleAuthor: $page->articleAuthor,
            articleTags: $page->articleTags,
        );
    }

    private function paginatedTitle(?string $title, int $page): ?string
    {
        if ($title === null || trim($title) === '') {
            return $title;
        }

        return sprintf(
            '%s %s',
            trim($title),
            __('app-meta.pagination.title_suffix', ['page' => $page])
        );
    }

    private function paginatedDescription(?string $description, int $page): string
    {
        $prefix = __('app-meta.pagination.description_prefix', ['page' => $page]);

        if ($description === null || trim($description) === '') {
            return $prefix;
        }

        return $prefix . trim($description);
    }

    private function withPageQuery(string $url, int $page): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query['page'] = $page;

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        $queryString = http_build_query($query);

        return $scheme . $host . $port . $path . ($queryString !== '' ? '?' . $queryString : '') . $fragment;
    }
}
