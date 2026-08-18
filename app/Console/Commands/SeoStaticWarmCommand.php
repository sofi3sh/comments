<?php

namespace App\Console\Commands;

use App\Models\Site\Site;
use App\Repositories\SeoRepository;
use App\Services\Article\StaticFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SeoStaticWarmCommand extends Command
{

    /**
     *                   E x a m p l e s
     * php artisan seo:static-warm
     * php artisan seo:static-warm --site=category1.appdomain.com
     * php artisan seo:static-warm --site=category1 --force
     */

    protected $signature = 'seo:static-warm
                            {--site= : Only warm one site (slug or domain)}
                            {--force : Deprecated no-op — every file is always regenerated}';

    protected $description = 'Pre-generate per-site static SEO files (robots.txt, sitemap index, sitemap pages)';

    public function handle(SeoRepository $seo, StaticFileService $static): int
    {
        $sites = $this->sites();

        if ($sites->isEmpty()) {
            $this->warn('No matching active sites.');

            return self::FAILURE;
        }

        foreach ($sites as $site) {
            // [path => [renderer, last-modified]]. The timestamp is stored as
            // object metadata and becomes the served Last-Modified, so it has to
            // describe the content: the site row for robots.txt, the newest
            // article in the bucket for a sitemap page.
            $files = [
                'robots.txt' => [fn (): string => $seo->robots($site), $site->updated_at],
            ];

            foreach ($seo->sitemapPages($site) as ['locale' => $locale, 'page' => $page, 'last_modified' => $lastModified]) {
                $files[SeoRepository::sitemapPath($locale, $page)] = [
                    fn (): string => $seo->sitemap($site, $locale, $page),
                    $lastModified,
                ];
            }

            // The index lists the news sitemaps, whose set moves with the clock,
            // so its own mtime is now(). The news files themselves belong to
            // seo:news-warm.
            $files[SeoRepository::INDEX_PATH] = [fn (): string => $seo->sitemapIndex($site), null];

            // No exists() gate: on S3 that was one network HEAD per file per run
            // (30+ domains × locales × sitemap buckets on a */15 cron). Writing
            // is cheaper than asking, and the result is identical.
            $written = 0;

            foreach ($files as $path => [$render, $lastModified]) {
                $static->storePublic($site->domain, $path, $render(), $lastModified);
                $written++;
            }

            $this->info(sprintf('%s: %d file(s) written.', $site->domain, $written));
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Site>
     */
    private function sites(): Collection
    {
        $filter = (string) $this->option('site');

        return Site::query()
            ->where('active', true)
            ->when($filter !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('slug', $filter)
                    ->orWhere('domain', $filter)
            ))
            ->get();
    }
}
