<?php

namespace App\Console\Commands;

use App\Models\Site\Site;
use App\Repositories\SeoRepository;
use App\Services\Article\StaticFileService;
use App\Services\StaticCache\SeoStaticInvalidator;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SeoNewsWarmCommand extends Command
{

    /**
     *                   E x a m p l e s
     * php artisan seo:news-warm
     * php artisan seo:news-warm --site=category1.appdomain.com
     */

    protected $signature = 'seo:news-warm
                            {--site= : Only warm one site (slug or domain)}';

    protected $description = 'Regenerate the per-locale Google News sitemaps and the sitemap index';

    /**
     * Article changes already drop the news sitemaps through
     * SeoStaticInvalidator, so publishing is covered by events. This command
     * exists for the other direction: an article leaving the news window is
     * not a change to anything, so nothing fires and the file would keep
     * listing it. Files are therefore always rewritten, never skipped.
     */
    public function handle(
        SeoRepository $seo,
        StaticFileService $static,
        SeoStaticInvalidator $invalidator
    ): int {
        $sites = $this->sites();

        if ($sites->isEmpty()) {
            $this->warn('No matching active sites.');

            return self::FAILURE;
        }

        foreach ($sites as $site) {
            $locales = $seo->newsLocales($site);

            foreach ($locales as $locale) {
                $static->storePublic(
                    $site->domain,
                    SeoRepository::newsSitemapPath($locale),
                    $seo->newsSitemap($site, $locale)
                );
            }

            $invalidator->pruneNewsSitemaps($site->domain, $locales);

            // The index lists the news sitemaps, so it follows them: a locale
            // that just ran out of news must stop being advertised.
            $static->storePublic(
                $site->domain,
                SeoRepository::INDEX_PATH,
                $seo->sitemapIndex($site)
            );

            $this->info(sprintf(
                '%s: news sitemaps written for [%s], index refreshed.',
                $site->domain,
                $locales === [] ? '-' : implode(', ', $locales)
            ));
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
