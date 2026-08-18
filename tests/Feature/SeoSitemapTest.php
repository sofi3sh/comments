<?php

namespace Tests\Feature;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Marker;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\Articles\Translate\MarkerTranslation;
use App\Models\Settings\Locale;
use App\Models\Site\Site;
use App\Repositories\SeoRepository;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoSitemapTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private SeoRepository $seo;

    protected function setUp(): void
    {
        parent::setUp();

        // Article and site observers delete static files; keep them off the
        // real static-public volume.
        Storage::fake('static-public');
        Storage::fake('static-private');

        $this->site = Site::create([
            'name' => 'Test Publication',
            'slug' => 'test',
            'domain' => 'test.example',
            'active' => true,
        ]);

        Locale::create(['name' => 'Українська', 'code' => 'uk', 'prefix' => 'uk', 'is_default' => true, 'is_active' => true]);
        Locale::create(['name' => 'Русский', 'code' => 'ru', 'prefix' => 'ru', 'is_default' => false, 'is_active' => true]);
        Locale::create(['name' => 'English', 'code' => 'en', 'prefix' => 'en', 'is_default' => false, 'is_active' => true]);
        Locale::clearAll();

        // Pin the news window: the configured default is an operational knob
        // and these expectations must not move with it.
        config(['seo.news.window_hours' => 48]);

        $this->seo = app(SeoRepository::class);
    }

    /* ---------------------------------------------------------------- *
     |  Per-locale sitemap pages
     * ---------------------------------------------------------------- */

    public function test_a_locale_file_only_lists_articles_translated_into_that_locale(): void
    {
        $both = $this->article(['uk' => 'obidva', 'ru' => 'oba']);
        $ukOnly = $this->article(['uk' => 'lyshe-uk']);

        $ru = $this->seo->sitemap($this->site, 'ru', 1);

        $this->assertStringContainsString('/ru/news/oba-'.$both->id.'.html', $ru);
        $this->assertStringNotContainsString('-'.$ukOnly->id.'.html</loc>', $ru);
        $this->assertSame(1, substr_count($ru, '<url>'));

        $uk = $this->seo->sitemap($this->site, 'uk', 1);

        $this->assertSame(2, substr_count($uk, '<url>'));
    }

    public function test_every_loc_carries_the_files_own_locale_prefix(): void
    {
        $this->article(['uk' => 'a-uk', 'ru' => 'a-ru']);
        $this->article(['uk' => 'b-uk', 'ru' => 'b-ru']);

        preg_match_all('~<loc>(.+?)</loc>~', $this->seo->sitemap($this->site, 'ru', 1), $matches);

        $this->assertCount(2, $matches[1]);

        foreach ($matches[1] as $loc) {
            $this->assertStringStartsWith('https://test.example/ru/', $loc);
        }
    }

    public function test_alternates_are_complete_and_identical_across_locale_files(): void
    {
        $article = $this->article(['uk' => 'a-uk', 'ru' => 'a-ru', 'en' => 'a-en']);

        $fromUk = $this->alternates($this->seo->sitemap($this->site, 'uk', 1));
        $fromRu = $this->alternates($this->seo->sitemap($this->site, 'ru', 1));

        $this->assertSame($fromUk, $fromRu);

        $this->assertSame([
            'en' => 'https://test.example/en/news/a-en-'.$article->id.'.html',
            'ru' => 'https://test.example/ru/news/a-ru-'.$article->id.'.html',
            'uk' => 'https://test.example/uk/news/a-uk-'.$article->id.'.html',
            // x-default stays on the default locale in every file.
            'x-default' => 'https://test.example/uk/news/a-uk-'.$article->id.'.html',
        ], $fromUk);
    }

    public function test_inactive_locales_are_never_listed(): void
    {
        $en = Locale::where('code', 'en')->first();
        $en->is_active = false;
        $en->save();
        Locale::clearAll();

        $this->article(['uk' => 'a-uk', 'en' => 'a-en']);

        $this->assertStringNotContainsString('/en/', $this->seo->sitemap($this->site, 'uk', 1));
        $this->assertStringNotContainsString('sitemap_en_', $this->seo->sitemapIndex($this->site));
    }

    public function test_translations_without_a_slug_are_skipped(): void
    {
        $article = $this->article(['uk' => 'a-uk']);

        // ArticleTranslation::booted() slugifies the title whenever the slug
        // is empty, so an unlinkable translation is one that has neither - a
        // locale that was started but never written.
        $empty = ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'ru',
            'title' => null,
        ]);

        $this->assertNull($empty->fresh()->slug);

        $this->assertSame(0, substr_count($this->seo->sitemap($this->site, 'ru', 1), '<url>'));
        $this->assertStringNotContainsString('sitemap_ru_', $this->seo->sitemapIndex($this->site));
        $this->assertStringNotContainsString('news_ru.xml', $this->seo->sitemapIndex($this->site));
    }

    public function test_future_dated_and_undated_articles_are_excluded(): void
    {
        $this->article(['uk' => 'live']);
        $this->article(['uk' => 'future'], ['published_at' => now()->addDay()]);
        $this->article(['uk' => 'undated'], ['published_at' => null]);
        $this->article(['uk' => 'draft'], ['status' => Article::STATUS_DRAFT]);

        $uk = $this->seo->sitemap($this->site, 'uk', 1);

        $this->assertSame(1, substr_count($uk, '<url>'));
        $this->assertStringContainsString('live-', $uk);
    }

    public function test_articles_of_another_site_are_excluded(): void
    {
        $other = Site::create([
            'name' => 'Other',
            'slug' => 'other',
            'domain' => 'other.example',
            'active' => true,
        ]);

        $this->article(['uk' => 'mine']);
        $this->article(['uk' => 'theirs'], [], $other);

        $this->assertSame(1, substr_count($this->seo->sitemap($this->site, 'uk', 1), '<url>'));
    }

    /* ---------------------------------------------------------------- *
     |  Sitemap index
     * ---------------------------------------------------------------- */

    public function test_index_lists_one_entry_per_locale_bucket_and_the_news_files(): void
    {
        $this->article(['uk' => 'a-uk', 'ru' => 'a-ru']);

        $index = $this->seo->sitemapIndex($this->site);

        $this->assertStringContainsString('https://test.example/sitemaps/sitemap_uk_1.xml', $index);
        $this->assertStringContainsString('https://test.example/sitemaps/sitemap_ru_1.xml', $index);
        $this->assertStringNotContainsString('sitemap_en_1.xml', $index);
        $this->assertStringContainsString('https://test.example/sitemaps/news_uk.xml', $index);
        $this->assertStringContainsString('https://test.example/sitemaps/news_ru.xml', $index);

        $pages = $this->seo->sitemapPages($this->site);

        $this->assertSame([
            ['locale' => 'ru', 'page' => 1],
            ['locale' => 'uk', 'page' => 1],
        ], array_map(
            static fn (array $page): array => ['locale' => $page['locale'], 'page' => $page['page']],
            $pages
        ));

        // Each bucket carries the newest article/translation timestamp in it —
        // the warm command stores it as the object's Last-Modified.
        foreach ($pages as $page) {
            $this->assertInstanceOf(CarbonInterface::class, $page['last_modified']);
        }
    }

    public function test_index_omits_news_files_that_would_be_empty(): void
    {
        $this->article(['uk' => 'old'], ['published_at' => now()->subDays(30)]);

        $index = $this->seo->sitemapIndex($this->site);

        $this->assertStringContainsString('sitemap_uk_1.xml', $index);
        $this->assertStringNotContainsString('news_uk.xml', $index);
        $this->assertSame([], $this->seo->newsLocales($this->site));
    }

    /* ---------------------------------------------------------------- *
     |  Google News sitemaps
     * ---------------------------------------------------------------- */

    public function test_news_entry_carries_the_full_publication_record(): void
    {
        $article = $this->article(['uk' => 'svizha-novyna']);

        $xml = $this->seo->newsSitemap($this->site, 'uk');

        $this->assertStringContainsString('xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"', $xml);
        $this->assertStringContainsString('<loc>https://test.example/uk/news/svizha-novyna-'.$article->id.'.html</loc>', $xml);
        $this->assertStringContainsString('<news:name>Test Publication</news:name>', $xml);
        $this->assertStringContainsString('<news:language>uk</news:language>', $xml);
        $this->assertStringContainsString('<news:title>Title svizha-novyna uk</news:title>', $xml);
        $this->assertStringContainsString('<news:publication_date>'.$article->published_at->toAtomString().'</news:publication_date>', $xml);

        // News files carry no hreflang annotations and no <lastmod>.
        $this->assertStringNotContainsString('xhtml:link', $xml);
        $this->assertStringNotContainsString('<lastmod>', $xml);
    }

    public function test_news_title_matches_the_marker_prefixed_headline_shown_on_the_page(): void
    {
        $article = Article::create([
            'type_id' => $this->type('news')->id,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        // Markers must be attached before the translation is saved: the
        // observer composes title_with_markers in the saving hook.
        $marker = Marker::create(['code' => 'exclusive', 'is_active' => true]);
        MarkerTranslation::create(['marker_id' => $marker->id, 'locale' => 'uk', 'name' => 'Ексклюзив']);
        $article->markers()->attach($marker->id);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'uk',
            'title' => 'Заголовок статті',
            'slug' => 'zagolovok',
        ]);

        $article->sites()->attach($this->site->getKey());

        $xml = $this->seo->newsSitemap($this->site, 'uk');

        $this->assertStringContainsString('<news:title>Ексклюзив Заголовок статті</news:title>', $xml);
    }

    public function test_news_title_falls_back_to_the_bare_headline_without_markers(): void
    {
        $this->article(['uk' => 'bez-markeriv']);

        $this->assertStringContainsString(
            '<news:title>Title bez-markeriv uk</news:title>',
            $this->seo->newsSitemap($this->site, 'uk')
        );
    }

    public function test_news_publication_name_can_be_overridden_per_locale(): void
    {
        config(['seo.news.publication_name' => ['uk' => 'Коментарі']]);

        $this->article(['uk' => 'a-uk', 'ru' => 'a-ru']);

        $this->assertStringContainsString('<news:name>Коментарі</news:name>', $this->seo->newsSitemap($this->site, 'uk'));
        $this->assertStringContainsString('<news:name>Test Publication</news:name>', $this->seo->newsSitemap($this->site, 'ru'));
    }

    public function test_news_sitemap_respects_the_publication_window(): void
    {
        config(['seo.news.window_hours' => 48]);

        $this->article(['uk' => 'fresh'], ['published_at' => now()->subHours(2)]);
        $this->article(['uk' => 'stale'], ['published_at' => now()->subHours(72)]);

        $xml = $this->seo->newsSitemap($this->site, 'uk');

        $this->assertStringContainsString('fresh-', $xml);
        $this->assertStringNotContainsString('stale-', $xml);
    }

    public function test_news_sitemap_respects_the_type_allowlist(): void
    {
        $this->article(['uk' => 'novyna']);
        $this->article(['uk' => 'reliz'], ['type_id' => $this->type('press_rls')->id]);

        $xml = $this->seo->newsSitemap($this->site, 'uk');

        $this->assertStringContainsString('novyna-', $xml);
        $this->assertStringNotContainsString('reliz-', $xml);
    }

    public function test_news_sitemap_respects_the_url_cap_and_orders_by_recency(): void
    {
        config(['seo.news.max_urls' => 1]);

        $this->article(['uk' => 'older'], ['published_at' => now()->subHours(5)]);
        $newest = $this->article(['uk' => 'newest'], ['published_at' => now()->subHour()]);

        $xml = $this->seo->newsSitemap($this->site, 'uk');

        $this->assertSame(1, substr_count($xml, '<url>'));
        $this->assertStringContainsString('newest-'.$newest->id, $xml);
    }

    public function test_news_sitemap_skips_translations_without_a_title(): void
    {
        $article = $this->article(['uk' => 'a-uk']);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'ru',
            'title' => null,
            'slug' => 'bez-zagolovka',
        ]);

        $this->assertSame(0, substr_count($this->seo->newsSitemap($this->site, 'ru'), '<url>'));
        $this->assertSame(['uk'], $this->seo->newsLocales($this->site));
    }

    /* ---------------------------------------------------------------- *
     |  Fixtures
     * ---------------------------------------------------------------- */

    /**
     * @param array<string, string> $slugsByLocale
     * @param array<string, mixed> $attributes
     */
    private function article(array $slugsByLocale, array $attributes = [], ?Site $site = null): Article
    {
        $article = Article::create($attributes + [
            'type_id' => $this->type('news')->id,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        foreach ($slugsByLocale as $locale => $slug) {
            ArticleTranslation::create([
                'article_id' => $article->id,
                'locale' => $locale,
                'title' => 'Title '.$slug.' '.$locale,
                'slug' => $slug,
            ]);
        }

        $article->sites()->attach(($site ?? $this->site)->getKey());

        return $article->fresh();
    }

    private function type(string $code): ArticleType
    {
        return ArticleType::firstOrCreate(['code' => $code], ['is_active' => true]);
    }

    /**
     * hreflang => href of the first <url> entry in a sitemap page.
     *
     * @return array<string, string>
     */
    private function alternates(string $xml): array
    {
        preg_match_all('~hreflang="([^"]+)" href="([^"]+)"~', $xml, $matches, PREG_SET_ORDER);

        $alternates = [];

        foreach ($matches as $match) {
            $alternates[$match[1]] = $match[2];
        }

        ksort($alternates);

        return $alternates;
    }
}
