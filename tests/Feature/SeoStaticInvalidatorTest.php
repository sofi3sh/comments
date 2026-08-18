<?php

namespace Tests\Feature;

use App\Models\Settings\Locale;
use App\Models\Site\Site;
use App\Repositories\SeoRepository;
use App\Services\StaticCache\SeoStaticInvalidator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoStaticInvalidatorTest extends TestCase
{
    use RefreshDatabase;

    private Filesystem $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disk = Storage::fake('static-public');
        Storage::fake('static-private');

        Site::create(['name' => 'Test', 'slug' => 'test', 'domain' => 'test.example', 'active' => true]);
        Site::resetAllCached();

        Locale::create(['name' => 'Українська', 'code' => 'uk', 'prefix' => 'uk', 'is_default' => true, 'is_active' => true]);
        Locale::create(['name' => 'Русский', 'code' => 'ru', 'prefix' => 'ru', 'is_default' => false, 'is_active' => true]);
        Locale::clearAll();
    }

    public function test_it_drops_every_locale_of_the_articles_bucket_plus_news_and_index(): void
    {
        // Bucket 2 covers IDs 40001..80000, so 40001 is its first article.
        $this->seedFiles([
            'sitemaps/sitemap_uk_2.xml',
            'sitemaps/sitemap_ru_2.xml',
            'sitemaps/news_uk.xml',
            'sitemaps/news_ru.xml',
            'sitemap.xml',
            'robots.txt',
            // Neighbouring buckets must survive: that stability is the whole
            // point of addressing sitemap files by ID range.
            'sitemaps/sitemap_uk_1.xml',
            'sitemaps/sitemap_uk_3.xml',
        ]);

        app(SeoStaticInvalidator::class)->invalidateForArticle(40001);

        $this->assertMissing('sitemaps/sitemap_uk_2.xml');
        $this->assertMissing('sitemaps/sitemap_ru_2.xml');
        $this->assertMissing('sitemaps/news_uk.xml');
        $this->assertMissing('sitemaps/news_ru.xml');
        $this->assertMissing('sitemap.xml');

        $this->assertPresent('sitemaps/sitemap_uk_1.xml');
        $this->assertPresent('sitemaps/sitemap_uk_3.xml');
        $this->assertPresent('robots.txt');
    }

    public function test_the_php_bucket_matches_the_boundaries_the_sql_expression_uses(): void
    {
        $perFile = SeoRepository::ARTICLES_PER_SITEMAP;

        foreach ([1 => 1, $perFile => 1, $perFile + 1 => 2, 2 * $perFile => 2] as $articleId => $bucket) {
            $this->seedFiles(['sitemaps/sitemap_uk_'.$bucket.'.xml']);

            // Repeat invalidations of one bucket are suppressed for the
            // lifetime of a request; each boundary needs a clean slate.
            request()->attributes->remove('seo_invalidated_buckets');

            app(SeoStaticInvalidator::class)->invalidateForArticle($articleId);

            $this->assertMissing(
                'sitemaps/sitemap_uk_'.$bucket.'.xml',
                'Article '.$articleId.' should fall into bucket '.$bucket.'.'
            );
        }
    }

    public function test_a_bucket_is_only_invalidated_once_per_request(): void
    {
        app(SeoStaticInvalidator::class)->invalidateForArticle(1);

        // The second article shares bucket 1, so the file recreated in between
        // survives: a burst of edits costs one delete, not one per article.
        $this->seedFiles(['sitemaps/sitemap_uk_1.xml']);

        app(SeoStaticInvalidator::class)->invalidateForArticle(2);

        $this->assertPresent('sitemaps/sitemap_uk_1.xml');
    }

    public function test_pruning_removes_only_the_news_files_no_longer_advertised(): void
    {
        $this->seedFiles(['sitemaps/news_uk.xml', 'sitemaps/news_ru.xml']);

        app(SeoStaticInvalidator::class)->pruneNewsSitemaps('test.example', ['uk']);

        $this->assertPresent('sitemaps/news_uk.xml');
        $this->assertMissing('sitemaps/news_ru.xml');
    }

    /**
     * @param list<string> $paths
     */
    private function seedFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->disk->put(brotliPath(sitePath('test.example', $path)), 'x');
        }
    }

    private function assertMissing(string $path, string $message = ''): void
    {
        $this->disk->assertMissing(brotliPath(sitePath('test.example', $path)), $message);
    }

    private function assertPresent(string $path): void
    {
        $this->disk->assertExists(brotliPath(sitePath('test.example', $path)));
    }
}
