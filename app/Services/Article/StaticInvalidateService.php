<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Services\StaticCache\SeoStaticInvalidator;
use App\Services\StaticCache\StaticDiskDeleter;
use App\Support\LogChannels;

class StaticInvalidateService
{
    /**
     * Enables verbose static invalidation logs when configured.
     */
    protected bool $toLog;

    /**
     * Paths already invalidated during the current non-HTTP execution.
     *
     * HTTP requests store this state in request attributes instead, so the
     * state cannot leak between requests in long-running workers.
     *
     * @var array<string, bool>
     */
    private array $invalidatedPaths = [];

    /**
     * Article IDs already fully invalidated during the current non-HTTP execution.
     *
     * HTTP requests store this state in request attributes instead.
     *
     * @var array<int, bool>
     */
    private array $invalidatedArticles = [];

    public function __construct(
        private readonly ArticleStaticPathResolver $pathResolver,
        private readonly SeoStaticInvalidator $seoInvalidator,
    ) {
        $this->toLog = (bool) config('logging.test.info', false);
    }

    /**
     * Invalidate all static files for every translation of an article.
     *
     * Use this when article-level fields changed, for example category, type,
     * status, publication date, or other fields that may affect all localized
     * static pages. The method marks the article as processed for the current
     * request to avoid repeating the same full invalidation.
     *
     * @param Article $article
     * @return void
     */
    public function invalidateArticle(Article $article): void
    {
        if ($this->wasArticleInvalidated($article->getKey())) {
            return;
        }

        foreach ($this->pathResolver->pathsForArticle($article) as $pathSet) {
            $this->invalidatePathSet('Invalidate static article paths', [
                'article_id' => $article->getKey(),
                'locale'     => $pathSet['locale'],
                'path'       => $pathSet['path'],
            ], $pathSet);
        }

        $this->seoInvalidator->invalidateForArticle((int) $article->getKey());
    }


    /**
     * Check whether the whole article was already invalidated in this request.
     *
     * Translation observers use this to skip per-translation invalidation after
     * an article-level invalidation has already removed all localized files.
     *
     * @param int $articleId
     * @return bool
     */
    public function isArticleInvalidated(int $articleId): bool
    {
        try {
            $request = request();
            $articles = $request->attributes->get('static_invalidated_articles', []);

            return isset($articles[$articleId]);
        } catch (\Throwable) {
            return isset($this->invalidatedArticles[$articleId]);
        }
    }


    /**
     * Invalidate the old static path of a translation before it is saved.
     *
     * Use this from an updating/deleting event when the old locale or slug may
     * still exist on disk. The resolver reads original model values to build the
     * path that existed before the current changes.
     *
     * @param ArticleTranslation $translation
     * @return void
     */
    public function invalidateOriginalTranslationPath(ArticleTranslation $translation): void
    {
        // Sitemap files depend on the article regardless of whether a static
        // page path can be built; bucket dedup makes repeated calls cheap.
        $this->seoInvalidator->invalidateForArticle((int) $translation->article_id);

        $pathSet = $this->pathResolver->originalPathForTranslation($translation);

        if ($pathSet === null) {
            return;
        }

        $this->invalidatePathSet('Invalidate original static article translation path', [
            'article_id' => $translation->article_id,
            'locale' => $pathSet['locale'],
            'path' => $pathSet['path'],
        ], $pathSet);
    }


    /**
     * Invalidate the current static path of one article translation.
     *
     * Use this when only translation-level data changed, such as title, slug,
     * excerpt, content, or content_html. It deletes only this locale's public
     * and private static files.
     *
     * @param ArticleTranslation $translation
     * @return void
     */
    public function invalidateTranslation(ArticleTranslation $translation): void
    {
        $this->seoInvalidator->invalidateForArticle((int) $translation->article_id);

        $pathSet = $this->pathResolver->pathForTranslation($translation);

        if ($pathSet === null) {
            return;
        }

        $this->invalidatePathSet('Invalidate static article translation path', [
            'article_id' => $translation->article_id,
            'locale' => $pathSet['locale'],
            'path' => $pathSet['path'],
        ], $pathSet);
    }


    /**
     * Invalidate one resolved static path set.
     *
     * A path set contains one canonical HTML path and the matching public/private
     * files, including compressed variants. This method also deduplicates by
     * path, so the same file set is not deleted twice during one request.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @param array{path: string, public: list<string>, private: list<string>} $pathSet
     * @return void
     */
    protected function invalidatePathSet(string $message, array $context, array $pathSet): void
    {
        if ($this->wasInvalidated($pathSet['path'])) {
            return;
        }

        $this->toLog && logTo(LogChannels::DEBUG)->info($message, $context);

        $this->deleteStaticSet($pathSet['public'], $pathSet['private']);
    }


    /**
     * Delete one static path set from both configured disks.
     *
     * Public files contain the rendered full page. Private files contain the
     * rest-content fragment loaded by the article page.
     *
     * @param list<string> $publicFiles
     * @param list<string> $privateFiles
     * @return void
     */
    protected function deleteStaticSet(array $publicFiles, array $privateFiles): void
    {
        $this->deleteFiles('static-public', $publicFiles);
        $this->deleteFiles('static-private', $privateFiles);
    }


    /**
     * Delete a list of files from one disk using Laravel's bulk delete.
     *
     * Missing files are ignored by the filesystem adapter, which is useful for
     * invalidation because not every article necessarily has every static file.
     *
     * @param string $diskName
     * @param list<string> $files
     * @return void
     */
    protected function deleteFiles(string $diskName, array $files): void
    {
        app(StaticDiskDeleter::class)->delete($diskName, $files);

        $this->toLog && logTo(LogChannels::DEBUG)->info('Static files delete requested', [
            'disk' => $diskName,
            'paths' => $files,
        ]);
    }

    /**
     * Check and mark a single static path as already invalidated.
     *
     * Returns true when the path was already processed. Returns false on the
     * first call and records the path immediately. In HTTP requests the marker
     * is stored on the request object; in console/jobs it falls back to the
     * service instance property.
     *
     * @param string $path
     * @return bool
     */
    private function wasInvalidated(string $path): bool
    {
        if (app()->bound('request')) {
            $request = request();
            $paths = $request->attributes->get('static_invalidated_paths', []);

            if (isset($paths[$path])) {
                return true;
            }

            $paths[$path] = true;
            $request->attributes->set('static_invalidated_paths', $paths);

            return false;
        }

        if (isset($this->invalidatedPaths[$path])) {
            return true;
        }

        $this->invalidatedPaths[$path] = true;

        return false;
    }

    /**
     * Check and mark an article as already fully invalidated.
     *
     * Returns true when the article was already processed. Returns false on the
     * first call and records the article ID immediately. This prevents repeated
     * all-locale invalidation when multiple model events fire in one request.
     *
     * @param int|null $articleId
     * @return bool
     */
    private function wasArticleInvalidated(?int $articleId): bool
    {
        if ($articleId === null) {
            return false;
        }

        if (app()->bound('request')) {
            $request = request();
            $articles = $request->attributes->get('static_invalidated_articles', []);

            if (isset($articles[$articleId])) {
                return true;
            }

            $articles[$articleId] = true;
            $request->attributes->set('static_invalidated_articles', $articles);

            return false;
        }

        if (isset($this->invalidatedArticles[$articleId])) {
            return true;
        }

        $this->invalidatedArticles[$articleId] = true;

        return false;
    }
}
