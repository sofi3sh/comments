<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\Translate\ArticleTranslation;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class StaticFileService
{

    /**
     * description
     *
     * Алгоритм создания статического файла:
     * - нормализуем URL до path и проверяем, что он подходит для генерации;
     * - строим путь публичного brotli-файла по URI и путь приватной REST-части для типов, которые делятся на first/rest;
     * - сохраняем готовый HTML из articleData в static-public (перезаписываем всегда);
     * - если нужна приватная REST-часть, получаем статью, делим контент через ArticleContentService и сохраняем rest в static-private;
     * - в finally всегда снимаем Redis-lock генерации.
     *
     * Диски — S3 (MinIO локально, Hetzner в staging/prod), поэтому проверок
     * существования здесь нет: каждая была бы сетевым HEAD, а от повторной
     * генерации защищает Redis-lock. Перезапись дешевле проверки.
     */

    protected Filesystem $publicDisk;
    protected Filesystem $privateDisk;

    public function __construct(
        protected ArticleContentService $contentService,
        private ArticleContentSplitPolicy $splitPolicy,
    ) {
        $this->publicDisk = Storage::disk('static-public');
        $this->privateDisk = Storage::disk('static-private');
    }


    /**
     * @param string $uri
     * @param object $articleData
     * @param string $lockKey
     * @return void
     */
    public function generate(string $uri, object $articleData, string $lockKey): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        try {
            if (!$this->isAllowedUri($uri)) {
                return;
            }

            $lastModified = $this->lastModifiedOf($articleData);

            $relativePath = sitePath($articleData->host, $this->relativePathFromUri($uri));

            $this->storeBrotli($this->publicDisk, $relativePath, $articleData->html, $lastModified);

            $restPath = $this->privateRestPath($articleData);
            $restHtml = $restPath !== null
                ? $this->getRestContent($articleData)
                : null;

            if ($restPath !== null && $restHtml) {
                $this->storeBrotli($this->privateDisk, $restPath, $restHtml, $lastModified, public: false);
            }

        } finally {
            Redis::del($lockKey);
        }
    }

    private function getRestContent(object $articleData): ?string
    {
        if (!$this->hasPrivateRest($articleData)) {
            return null;
        }

        $article = Article::find($articleData->articleId);

        if (!$article) {
            return null;
        }

        if (!empty($articleData->locale)) {
            app()->setLocale($articleData->locale);
        }

        $parts = $this->contentService->splitContent($article);

        return $parts->rest;
    }


    /**
     * @param string $uri
     * @return string
     */
    private function normalizeUri(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        return '/' . trim($uri, '/');
    }

    private function relativePathFromUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '';

        return ltrim($path, '/');
    }

    /**
     * Условия:
     * - min 2 сегментов
     * - максимум 5 сегментов
     * - последний сегмент = файл (.html)
     */
    private function isAllowedUri(string $uri): bool
    {
        $segments = explode('/', trim($uri, '/'));

        // максимум 5 сегментов
        if (count($segments) > 5) {
            return false;
        }

        // min 2 (type + file)
        if (count($segments) < 2) {
            return false;
        }

        $last = end($segments);

        if ($last === false || $last === '') {
            return false;
        }

        return true;
    }

    /**
     * Store an already rendered public file (robots.txt, sitemaps, ...) as a
     * precompressed .br under the site host namespace. Overwrites existing.
     *
     * @param string $host
     * @param string $path
     * @param string $content
     * @param DateTimeInterface|null $lastModified
     * @return void
     */
    public function storePublic(
        string $host,
        string $path,
        string $content,
        ?DateTimeInterface $lastModified = null,
    ): void
    {
        $this->storeBrotli($this->publicDisk, sitePath($host, $path), $content, $lastModified);
    }

    /**
     * Пишем объект вместе с метаданными, потому что отдаёт его напрямую nginx,
     * а не PHP: mime и Content-Encoding больше неоткуда взять.
     *
     * Last-Modified у S3 задать нельзя — он всегда равен времени PUT. Кладём
     * дату из БД в пользовательские метаданные (x-amz-meta-last-modified),
     * nginx превращает её обратно в заголовок.
     */
    private function storeBrotli(
        Filesystem $disk,
        string $path,
        string $content,
        ?DateTimeInterface $lastModified = null,
        bool $public = true,
    ): void
    {
        $disk->put(brotliPath($path), $this->brotliCompress($content), [
            'ContentType'     => $this->contentTypeFor($path),
            'ContentEncoding' => 'br',
            'CacheControl'    => $public
                ? 'public, max-age=3600, must-revalidate'
                : 'private, no-store',
            'Metadata'        => [
                // RFC7231 hardcodes the "GMT" suffix, so convert first.
                'last-modified' => ($lastModified ? Carbon::instance($lastModified) : Carbon::now())
                    ->utc()
                    ->format(DateTimeInterface::RFC7231),
            ],
        ]);
    }

    /**
     * Тип определяем по пути ДО .br — иначе detector вернёт octet-stream и
     * браузер скачает brotli-блоб вместо страницы.
     */
    private function contentTypeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'xml'   => 'application/xml; charset=utf-8',
            'txt'   => 'text/plain; charset=utf-8',
            default => 'text/html; charset=utf-8',
        };
    }

    private function lastModifiedOf(object $pageData): ?DateTimeInterface
    {
        $value = $pageData->lastModified ?? null;

        return $value instanceof DateTimeInterface ? $value : null;
    }

    private function brotliCompress(string $content): string
    {
        if (function_exists('brotli_compress')) {
            return brotli_compress($content, 5);
        }

        throw new \RuntimeException('PHP Brotli extension is not available.');
    }

    /**
     * Определяем перевод статьи по URL
     */
    public function resolveTranslation($articleId, string $locale): ?ArticleTranslation
    {
        $article = Article::query()
            ->byIdOrOldId($articleId)
            ->first();

        if (!$article) {
            return null;
        }

        $locale = $locale === 'ua'
            ? 'uk'
            : $locale;

        return $article->translate($locale);
    }

    /**
     * @param Request $request
     * @return array|null
     */
    public function extractPageData(Request $request): ?object
    {
        $route = $request->route();

        if (!$route) {
            return null;
        }

        $id = $route->parameter('id');
        $locale = $route?->parameter('locale');
        $type = $route?->parameter('type');

        $locale = $locale ?? 'uk';

        if ($this->needsPrivateRest($type)) {
            if (!$id) {
                return null;
            }

            $translation = $this->resolveTranslation($id, $locale);

            if (!$translation) {
                return null;
            }

            return (object) [
                'kind'         => 'private',
                'type'         => $type,
                'articleId'    => (int) $translation->article_id,
                'entityId'     => (int) $translation->article_id,
                'locale'       => $translation->locale,
                'host'         => $request->getHost(),
                'lastModified' => $translation->updated_at,
            ];
        }

        // Capture only runs on article routes, so {id} is always an article id
        // here — it is safe to resolve. Missing translations are not fatal for
        // non-split types, they just cost us the DB timestamp.
        $translation = $id ? $this->resolveTranslation($id, $locale) : null;

        return (object) [
            'kind'         => 'public',
            'type'         => $type,
            'articleId'    => null,
            'entityId'     => $id ? (int) $id : null,
            'locale'       => $locale === 'ua' ? 'uk' : $locale,
            'host'         => $request->getHost(),
            'lastModified' => $translation?->updated_at,
        ];
    }

    private function needsPrivateRest(?string $type): bool
    {
        return $this->splitPolicy->shouldSplitType($type);
    }

    private function hasPrivateRest(object $pageData): bool
    {
        return ($pageData->kind ?? null) === 'private'
            && !empty($pageData->articleId)
            && !empty($pageData->locale);
    }

    private function privateRestPath(object $pageData): ?string
    {
        if (!$this->hasPrivateRest($pageData)) {
            return null;
        }

        return restPath($pageData->locale, (int) $pageData->articleId);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function acquireLock(string $key): bool
    {
        return (bool) Redis::set($key, 1, 'NX', 'EX', 300);
    }
}
