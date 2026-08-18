<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\Site\Site;

class ArticleStaticPathResolver
{
    public function __construct(
        private readonly ArticleUrlBuilder $urlBuilder,
    ) {}


    /**
     * @param Article $article
     * @return array
     */
    public function pathsForArticle(Article $article): array
    {
        $article->load([
            'type',
            'category',
            'translations' => fn ($query) => $query->withTrashed(),
        ]);

        $paths = [];

        foreach ($this->localesForArticle($article) as $locale) {
            $path = $this->pathForLocale($article, $locale);
            $pathRest = restPath($locale, $article->id);

            if ($path === null) {
                continue;
            }

            $paths[] = [
                'locale' => $locale,
                'path' => $path,
                ...$this->filesForPath($path, $pathRest),
            ];
        }

        return $paths;
    }


    /**
     * @param ArticleTranslation $translation
     * @return array|null
     */
    public function originalPathForTranslation(ArticleTranslation $translation): ?array
    {
        $article = $translation->article;

        $article->loadMissing([
            'type',
            'category',
        ]);

        $locale = $translation->getOriginal('locale') ?: $translation->locale;
        $slug = $translation->getOriginal('slug') ?: $translation->slug;
        $path = $this->pathForArticleData($article, $locale, $slug);
        $pathRest = restPath($translation->locale, $article->id);

        if ($path === null) {
            return null;
        }

        return [
            'locale' => $locale,
            'path' => $path,
            ...$this->filesForPath($path, $pathRest),
        ];
    }


    /**
     * @param ArticleTranslation $translation
     * @return array|null
     */
    public function pathForTranslation(ArticleTranslation $translation): ?array
    {
        $article = $translation->article;

        if ($article === null) {
            return null;
        }

        $article->loadMissing([
            'type',
            'category',
        ]);

        $path = $this->pathForArticleData($article, $translation->locale, $translation->slug);
        $pathRest = restPath($translation->locale, $article->id);

        if ($path === null) {
            return null;
        }

        return [
            'locale' => $translation->locale,
            'path' => $path,
            ...$this->filesForPath($path, $pathRest),
        ];
    }


    /**
     * @return list<string>
     */
    public function localesForArticle(Article $article): array
    {
        $article->loadMissing('translations');

        return $article->translations
            ->pluck('locale')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    public function pathForLocale(Article $article, string $locale): ?string
    {
        return $this->urlBuilder->pathFor($article, $locale);
    }


    protected function pathForArticleData(Article $article, string $locale, ?string $slug): ?string
    {
        return $this->urlBuilder->pathForData($article, $locale, $slug);
    }



    protected function filesForPath(string $path, string $pathRest): array
    {
        // Public files are stored per site host (sites/{host}/...) and the
        // capture host is not recoverable from the article alone, so delete
        // the path under every known site domain; missing files are no-ops.
        $public = array_map(
            static fn (string $domain): string => brotliPath(sitePath($domain, $path)),
            Site::getCachedDomains(),
        );

        return [
            'public' => $public,
            'private' => [
                brotliPath($pathRest),
            ],
        ];
    }
}
