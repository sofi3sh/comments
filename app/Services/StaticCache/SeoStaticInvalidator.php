<?php

namespace App\Services\StaticCache;

use App\Models\Settings\Locale;
use App\Models\Site\Site;
use App\Repositories\SeoRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class SeoStaticInvalidator
{
    private const DISK = 'static-public';

    private Filesystem $disk;

    /**
     * Sitemap buckets already invalidated during the current non-HTTP
     * execution. HTTP requests store this state in request attributes
     * instead, so the state cannot leak between requests in long-running
     * workers.
     *
     * @var array<int, bool>
     */
    private array $invalidatedBuckets = [];

    public function __construct(private readonly StaticDiskDeleter $deleter)
    {
        $this->disk = Storage::disk(self::DISK);
    }

    /**
     * Invalidate the sitemap files affected by one article change.
     *
     * Sitemap pages are ID buckets (see SeoRepository::ARTICLES_PER_SITEMAP),
     * so an article affects exactly one bucket - but one file per locale
     * within it, because an edit can add or remove a translation and thereby
     * change which locale files list the article. The sitemap index goes too,
     * since its <lastmod> changes with any article update.
     *
     * The capture host is not recoverable from the article alone, so the files
     * are deleted under every known site domain; missing files are no-ops.
     *
     * @param int $articleId
     * @return void
     */
    public function invalidateForArticle(int $articleId): void
    {
        $bucket = intdiv($articleId - 1, SeoRepository::ARTICLES_PER_SITEMAP) + 1;

        if ($this->wasBucketInvalidated($bucket)) {
            return;
        }

        $locales = $this->locales();
        $files = [];

        foreach (Site::getCachedDomains() as $domain) {
            foreach ($locales as $locale) {
                $files[] = brotliPath(sitePath($domain, SeoRepository::sitemapPath($locale, $bucket)));
                // A publish changes the news window for its locale, and the
                // article's locale is not known here either.
                $files[] = brotliPath(sitePath($domain, SeoRepository::newsSitemapPath($locale)));
            }

            $files[] = brotliPath(sitePath($domain, SeoRepository::INDEX_PATH));
        }

        $this->deleter->delete(self::DISK, $files);
    }

    /**
     * Drop the news sitemaps of one site host that are no longer advertised.
     *
     * A locale whose last article aged out of the news window disappears from
     * the index, but its file would otherwise stay on disk and keep serving
     * expired entries to anything requesting it directly.
     *
     * @param string $domain
     * @param list<string> $keepLocales
     * @return void
     */
    public function pruneNewsSitemaps(string $domain, array $keepLocales): void
    {
        $stale = array_diff($this->locales(), $keepLocales);

        if ($stale === []) {
            return;
        }

        $this->deleter->delete(self::DISK, array_map(
            static fn (string $locale): string => brotliPath(
                sitePath($domain, SeoRepository::newsSitemapPath($locale))
            ),
            array_values($stale)
        ));
    }

    /**
     * Invalidate one site's robots.txt; its content depends only on the site.
     *
     * @param string $domain
     * @return void
     */
    public function invalidateRobots(string $domain): void
    {
        $this->disk->delete(brotliPath(sitePath($domain, 'robots.txt')));
    }

    /**
     * Drop every static file of one site host (articles, robots, sitemaps).
     * Use when the site is deleted or its domain changes.
     *
     * @param string $domain
     * @return void
     */
    public function invalidateSite(string $domain): void
    {
        $this->disk->deleteDirectory('sites/'.$domain);
    }

    /**
     * Locale codes whose sitemap files exist on disk.
     *
     * @return list<string>
     */
    private function locales(): array
    {
        $codes = Locale::getActive()
            ->pluck('code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($codes === []) {
            $codes = array_keys((array) config('locales.available', []));
        }

        return array_values(array_map('strval', $codes));
    }

    /**
     * Check and mark a sitemap bucket as already invalidated.
     *
     * Returns true when the bucket was already processed. In HTTP requests
     * the marker is stored on the request object; in console/jobs it falls
     * back to the service instance property.
     *
     * @param int $bucket
     * @return bool
     */
    private function wasBucketInvalidated(int $bucket): bool
    {
        if (app()->bound('request')) {
            $request = request();
            $buckets = $request->attributes->get('seo_invalidated_buckets', []);

            if (isset($buckets[$bucket])) {
                return true;
            }

            $buckets[$bucket] = true;
            $request->attributes->set('seo_invalidated_buckets', $buckets);

            return false;
        }

        if (isset($this->invalidatedBuckets[$bucket])) {
            return true;
        }

        $this->invalidatedBuckets[$bucket] = true;

        return false;
    }
}
