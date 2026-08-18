<?php

namespace App\Repositories;

use App\Models\Articles\Article;
use App\Models\Scopes\CurrentSiteScope;
use App\Models\Settings\Locale;
use App\Models\Site\Site;
use App\Services\Article\ArticleUrlBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SeoRepository
{
    const ARTICLES_PER_SITEMAP = 40000;

    /**
     * Static path of the sitemap index, relative to the site host namespace.
     */
    public const INDEX_PATH = 'sitemap.xml';

    /**
     * Active locale codes of the current process, resolved once per instance.
     *
     * @var list<string>|null
     */
    private ?array $activeLocales = null;

    public function __construct(
        private readonly ArticleUrlBuilder $urlBuilder,
    ) {}

    /**
     * Path of one locale's sitemap page, relative to the site host namespace.
     *
     * Kept here so the repository, the warm-up commands and the static
     * invalidator all derive the file name from a single place.
     */
    public static function sitemapPath(string $locale, int $page): string
    {
        return 'sitemaps/sitemap_'.$locale.'_'.$page.'.xml';
    }

    /**
     * Path of one locale's Google News sitemap, relative to the site host
     * namespace.
     */
    public static function newsSitemapPath(string $locale): string
    {
        return 'sitemaps/news_'.$locale.'.xml';
    }

    /**
     * Per-site robots.txt rendered from a shared template; only the host
     * (and therefore the sitemap index URL) differs between subdomains.
     */
    public function robots(Site $site): string
    {
        return view('seo.robots', [
            'host' => $site->domain,
        ])->render();
    }

    /**
     * Sitemap index (/sitemap.xml) referencing the site's per-locale sitemap
     * pages (/sitemaps/sitemap_{locale}_{page}.xml) and its Google News
     * sitemaps (/sitemaps/news_{locale}.xml).
     */
    public function sitemapIndex(Site $site): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($this->newsBuckets($site) as $bucket) {
            $xml .= $this->indexEntry(
                $this->siteUrl($site, self::newsSitemapPath((string) $bucket->locale)),
                $bucket->last_modified
            );
        }

        foreach ($this->buckets($site) as $bucket) {
            $xml .= $this->indexEntry(
                $this->siteUrl($site, self::sitemapPath((string) $bucket->locale, (int) $bucket->sitemap)),
                $bucket->last_modified
            );
        }

        return $xml.'</sitemapindex>';
    }

    /**
     * Locale/page pairs of the site's sitemap files, i.e. the non-empty ID
     * buckets per locale. Used by the warm-up command to know which pages
     * exist.
     *
     * `last_modified` is the newest article/translation timestamp in the
     * bucket — the warm command stores it on the object so the served
     * Last-Modified reflects the content, not the moment we happened to write.
     *
     * @return list<array{locale: string, page: int, last_modified: Carbon|null}>
     */
    public function sitemapPages(Site $site): array
    {
        return $this->buckets($site)
            ->map(static fn ($bucket): array => [
                'locale' => (string) $bucket->locale,
                'page' => (int) $bucket->sitemap,
                'last_modified' => $bucket->last_modified
                    ? Carbon::parse($bucket->last_modified)
                    : null,
            ])
            ->all();
    }

    /**
     * Locales that currently have at least one article in their news window.
     *
     * @return list<string>
     */
    public function newsLocales(Site $site): array
    {
        return $this->newsBuckets($site)
            ->map(static fn ($bucket): string => (string) $bucket->locale)
            ->all();
    }

    /**
     * One sitemap page (/sitemaps/sitemap_{locale}_{page}.xml).
     *
     * Articles are selected by ID range rather than by limit/offset, so
     * sitemap_{locale}_{n}.xml always holds the same ID bucket; files hold a
     * different number of articles by design.
     *
     * An article appears in a locale's file only when it actually has that
     * translation, and its <loc> is that locale's URL. Every entry still
     * carries the full set of <xhtml:link rel="alternate"> annotations, which
     * hreflang requires to be present on all versions of a document.
     */
    public function sitemap(Site $site, string $locale, int $page): string
    {
        $fromId = ($page - 1) * self::ARTICLES_PER_SITEMAP + 1;
        $toId = $page * self::ARTICLES_PER_SITEMAP;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            .' xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        // A full bucket holds up to ARTICLES_PER_SITEMAP articles, so rows are
        // streamed in chunks instead of being hydrated all at once.
        $this->articlesForSite($site)
            ->with(['type', 'category', 'translations'])
            ->whereBetween('articles.id', [$fromId, $toId])
            ->whereHas('translations', fn (Builder $query) => $this->linkableTranslation($query, $locale))
            ->chunkById(500, function (Collection $articles) use ($site, $locale, &$xml): void {
                foreach ($articles as $article) {
                    $xml .= $this->urlEntry($site, $article, $locale);
                }
            }, 'articles.id', 'id');

        return $xml.'</urlset>';
    }

    /**
     * One locale's Google News sitemap (/sitemaps/news_{locale}.xml).
     *
     * Google reads news sitemaps as a rolling window of recent articles and
     * caps them at 1000 URLs, so this is a plain ordered+limited query rather
     * than an ID bucket: the file is rewritten as a whole on every warm run.
     */
    public function newsSitemap(Site $site, string $locale): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            .' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'."\n";

        $articles = $this->newsArticles($site)
            ->with(['type', 'category', 'translations'])
            ->whereHas('translations', fn (Builder $query) => $this->newsTranslation($query, $locale))
            ->orderByDesc('articles.published_at')
            ->limit($this->newsLimit())
            ->get();

        foreach ($articles as $article) {
            $xml .= $this->newsUrlEntry($site, $article, $locale);
        }

        return $xml.'</urlset>';
    }

    /**
     * Published articles of one site.
     *
     * CurrentSiteScope is dropped deliberately: it disables itself in console
     * context, so leaving it on would make the warm-up command and the HTTP
     * controller produce different sitemaps. The site filter is the explicit
     * article_sites constraint below, which behaves the same in both.
     *
     * status alone is not enough: an article can carry the published status
     * with an empty or future publication date, and neither belongs in a
     * sitemap.
     */
    private function articlesForSite(Site $site): Builder
    {
        return Article::query()
            ->withoutGlobalScope(CurrentSiteScope::class)
            ->where('articles.status', Article::STATUS_PUBLISHED)
            ->whereNotNull('articles.published_at')
            ->where('articles.published_at', '<=', now())
            ->whereHas('sites', fn ($query) => $query->whereKey($site->getKey()));
    }

    /**
     * Articles of one site inside the Google News window and of a type that
     * belongs in a news sitemap.
     */
    private function newsArticles(Site $site): Builder
    {
        return $this->articlesForSite($site)
            ->whereHas('type', fn (Builder $query) => $query->whereIn('code', $this->newsTypes()))
            ->where('articles.published_at', '>=', now()->subHours($this->newsWindowHours()));
    }

    /**
     * Non-empty locale/ID bucket pairs of one site with the last change in
     * each; used both for the sitemap index and for the list of existing
     * pages.
     *
     * The join is what splits the result per locale, so it also carries the
     * conditions that decide whether a translation can produce a URL at all -
     * a locale must never get a sitemap file it cannot fill.
     *
     * @return Collection<int, Article>
     */
    private function buckets(Site $site): Collection
    {
        $bucket = $this->bucketExpression();

        return $this->articlesForSite($site)
            ->join('article_translations', fn ($join) => $this->joinLinkableTranslations($join))
            ->whereIn('article_translations.locale', $this->activeLocales())
            ->selectRaw(
                "$bucket AS sitemap,"
                .' article_translations.locale AS locale,'
                .' GREATEST(MAX(articles.updated_at),'
                .' COALESCE(MAX(article_translations.updated_at), MAX(articles.updated_at)))'
                .' AS last_modified'
            )
            ->groupByRaw("$bucket, article_translations.locale")
            ->orderBy('locale')
            ->orderBy('sitemap')
            ->get();
    }

    /**
     * Locales of one site that currently have news, with the newest
     * publication date in each. Drives both the index entries and
     * newsLocales().
     *
     * @return Collection<int, Article>
     */
    private function newsBuckets(Site $site): Collection
    {
        return $this->newsArticles($site)
            ->join('article_translations', function ($join): void {
                $this->joinLinkableTranslations($join);

                $join->whereNotNull('article_translations.title')
                    ->where('article_translations.title', '<>', '');
            })
            ->whereIn('article_translations.locale', $this->activeLocales())
            ->selectRaw(
                'article_translations.locale AS locale,'
                .' MAX(articles.published_at) AS last_modified'
            )
            ->groupBy('article_translations.locale')
            ->orderBy('locale')
            ->get();
    }

    /**
     * Join conditions selecting the translations a public URL can be built
     * from. Soft-deleted rows and rows without a slug are excluded, because
     * ArticleUrlBuilder cannot produce a path for them.
     */
    private function joinLinkableTranslations(mixed $join): mixed
    {
        return $join->on('article_translations.article_id', '=', 'articles.id')
            ->whereNull('article_translations.deleted_at')
            ->whereNotNull('article_translations.slug')
            ->where('article_translations.slug', '<>', '');
    }

    /**
     * Same condition as joinLinkableTranslations(), expressed on the
     * translations relation. Soft deletes are handled by the model scope here.
     */
    private function linkableTranslation(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale)
            ->whereNotNull('slug')
            ->where('slug', '<>', '');
    }

    /**
     * A news entry additionally needs a headline for <news:title>.
     */
    private function newsTranslation(Builder $query, string $locale): Builder
    {
        return $this->linkableTranslation($query, $locale)
            ->whereNotNull('title')
            ->where('title', '<>', '');
    }

    /**
     * SQL for the ID bucket an article falls into. Must stay in step with
     * SeoStaticInvalidator::invalidateForArticle(), which computes the same
     * page number in PHP.
     *
     * The bucket size is interpolated rather than bound: under
     * only_full_group_by MySQL compares the SELECT and GROUP BY expressions
     * as parse trees, and two separate ? placeholders never match.
     */
    private function bucketExpression(): string
    {
        return 'FLOOR((articles.id - 1) / '.(int) self::ARTICLES_PER_SITEMAP.') + 1';
    }

    /**
     * One <sitemap> entry of the index.
     */
    private function indexEntry(string $url, mixed $lastModified): string
    {
        $xml = '  <sitemap>'."\n"
            .'    <loc>'.$this->escape($url).'</loc>'."\n";

        if (!empty($lastModified)) {
            $xml .= '    <lastmod>'.$this->timestamp($lastModified).'</lastmod>'."\n";
        }

        return $xml.'  </sitemap>'."\n";
    }

    /**
     * One <url> entry of a locale's sitemap page, or an empty string when no
     * public URL can be built for the article in that locale.
     */
    private function urlEntry(Site $site, Article $article, string $locale): string
    {
        $urls = $this->urlsByLocale($site, $article);

        if (!isset($urls[$locale])) {
            return '';
        }

        $xml = '  <url>'."\n"
            .'    <loc>'.$this->escape($urls[$locale]).'</loc>'."\n";

        $lastmod = $this->lastModified($article, $locale);

        if ($lastmod !== null) {
            $xml .= '    <lastmod>'.$lastmod.'</lastmod>'."\n";
        }

        // Every version links to all versions, itself included, as hreflang
        // annotations require.
        foreach ($urls as $alternateLocale => $url) {
            $xml .= $this->alternate($alternateLocale, $url);
        }

        // x-default stays on the default locale in every file, so all language
        // versions of one article agree on which is the unspecified fallback.
        $default = $this->defaultLocale();

        $xml .= $this->alternate(
            'x-default',
            $urls[$default] ?? $urls[array_key_first($urls)]
        );

        return $xml.'  </url>'."\n";
    }

    /**
     * One <url> entry of a news sitemap, or an empty string when the article
     * cannot produce a complete news record.
     */
    private function newsUrlEntry(Site $site, Article $article, string $locale): string
    {
        $path = $this->urlBuilder->pathFor($article, $locale);

        // The headline as the landing page shows it: the <h1>, <title> and
        // og:title all render the marker-prefixed form, and Google expects
        // <news:title> to match the page. It also keeps the pr and
        // partner_news markers - which are sponsorship disclosure - visible in
        // the feed. The accessor falls back to the bare title when the article
        // carries no markers.
        $title = trim((string) $article->translations->firstWhere('locale', $locale)?->title_with_marker);

        if ($path === null || $title === '' || $article->published_at === null) {
            return '';
        }

        return '  <url>'."\n"
            .'    <loc>'.$this->escape($this->siteUrl($site, $path)).'</loc>'."\n"
            .'    <news:news>'."\n"
            .'      <news:publication>'."\n"
            .'        <news:name>'.$this->escape($this->publicationName($site, $locale)).'</news:name>'."\n"
            .'        <news:language>'.$this->escape($locale).'</news:language>'."\n"
            .'      </news:publication>'."\n"
            .'      <news:publication_date>'.$this->timestamp($article->published_at).'</news:publication_date>'."\n"
            .'      <news:title>'.$this->escape($title).'</news:title>'."\n"
            .'    </news:news>'."\n"
            .'  </url>'."\n";
    }

    /**
     * Public absolute URL of every available translation, keyed by locale.
     *
     * ArticleUrlBuilder::pathFor() is used rather than urlForLocale(): the
     * latter resolves the host from the current site or request, neither of
     * which is bound when the warm-up command runs in console.
     *
     * @return array<string, string>
     */
    private function urlsByLocale(Site $site, Article $article): array
    {
        $active = $this->activeLocales();
        $urls = [];

        foreach ($article->translations as $translation) {
            $locale = (string) $translation->locale;

            if ($locale === '' || isset($urls[$locale]) || !in_array($locale, $active, true)) {
                continue;
            }

            $path = $this->urlBuilder->pathFor($article, $locale);

            if ($path === null) {
                continue;
            }

            $urls[$locale] = $this->siteUrl($site, $path);
        }

        // Translations come back in insertion order; sorting keeps the file
        // byte-identical between runs so unchanged sitemaps stay unchanged.
        ksort($urls);

        return $urls;
    }

    /**
     * Locale codes that may appear in sitemaps at all.
     *
     * Matches what the HTML <head> advertises, which also intersects the
     * article's translations with the active locales.
     *
     * @return list<string>
     */
    private function activeLocales(): array
    {
        if ($this->activeLocales !== null) {
            return $this->activeLocales;
        }

        $codes = Locale::getActive()
            ->pluck('code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($codes === []) {
            $codes = array_keys((array) config('locales.available', []));
        }

        return $this->activeLocales = array_values(array_map('strval', $codes));
    }

    /**
     * Locale used for the x-default annotation.
     *
     * The editorially configured default locale wins over config('app.locale'),
     * which is an environment setting and may differ per deployment.
     */
    private function defaultLocale(): string
    {
        return Locale::getDefault()?->code
            ?: (string) config('app.locale');
    }

    /**
     * Article type codes that belong in a news sitemap.
     *
     * @return list<string>
     */
    private function newsTypes(): array
    {
        return array_values((array) config('seo.news.types', []));
    }

    private function newsWindowHours(): int
    {
        return max(1, (int) config('seo.news.window_hours', 48));
    }

    private function newsLimit(): int
    {
        return max(1, (int) config('seo.news.max_urls', 1000));
    }

    /**
     * Publisher name for <news:publication>. Google registers one publication
     * per language edition, so the name may be overridden per locale; the site
     * name is the fallback.
     */
    private function publicationName(Site $site, string $locale): string
    {
        $names = (array) config('seo.news.publication_name', []);
        $name = $names[$locale] ?? null;

        return is_string($name) && $name !== ''
            ? $name
            : (string) $site->name;
    }

    private function alternate(string $hreflang, string $url): string
    {
        return '    <xhtml:link rel="alternate" hreflang="'.$this->escape($hreflang)
            .'" href="'.$this->escape($url).'"/>'."\n";
    }

    /**
     * Newest change of the article itself or of the translation behind <loc>.
     */
    private function lastModified(Article $article, string $locale): ?string
    {
        $translation = $article->translations->firstWhere('locale', $locale);

        $dates = array_filter([
            $article->updated_at,
            $translation?->updated_at,
        ]);

        return $dates === []
            ? null
            : $this->timestamp(max($dates));
    }

    private function siteUrl(Site $site, string $path): string
    {
        return 'https://'.$site->domain.'/'.$path;
    }

    private function timestamp(mixed $value): string
    {
        return Carbon::parse($value)->toAtomString();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
