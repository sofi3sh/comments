<?php

namespace App\Services\StaticCache;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\Settings\Locale;
use App\Models\Site\Site;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Диски — S3, поэтому здесь нет полных листингов бакета и нет exists() на
 * каждый перевод (для крупного типа это были сотни тысяч HTTP-HEAD).
 *
 * Публичные ключи ищем листингом по префиксу sites/{host}/{locale}/{type} —
 * именно это и описывала прежняя регулярка. Приватные строим из БД и удаляем
 * вслепую: DeleteObject по несуществующему ключу в S3 успешен.
 */
class ManualArticleStaticInvalidator
{
    private const PUBLIC_DISK = 'static-public';

    private const PRIVATE_DISK = 'static-private';

    private Filesystem $publicDisk;

    public function __construct(private readonly StaticDiskDeleter $deleter)
    {
        // No handle on the private disk: its keys come from the DB and are
        // deleted blind, so nothing here ever reads it.
        $this->publicDisk = Storage::disk(self::PUBLIC_DISK);
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return ArticleType::query()
            ->orderBy('code')
            ->pluck('code')
            ->filter()
            ->values()
            ->toArray();
    }

    public function preview(string $type): ManualArticleStaticInvalidationResult
    {
        return $this->invalidate($type, true);
    }

    /**
     * @return array<string, array{public: int, private: int}>
     */
    public function previewCounts(string $type): array
    {
        $result = $this->preview($type);

        return [
            $type => [
                'public' => $result->publicCount(),
                'private' => $result->privateCount(),
            ],
        ];
    }

    public function invalidate(string $type, bool $dryRun = false): ManualArticleStaticInvalidationResult
    {
        $articleType = $this->articleType($type);
        $paths = $this->matchingPaths($articleType);

        if ($dryRun || ($paths['public'] === [] && $paths['private'] === [])) {
            return new ManualArticleStaticInvalidationResult($type, $dryRun, $paths['public'], $paths['private']);
        }

        $failedPublic = $this->deleter->delete(self::PUBLIC_DISK, $paths['public']);
        $failedPrivate = $this->deleter->delete(self::PRIVATE_DISK, $paths['private']);

        return new ManualArticleStaticInvalidationResult(
            $type,
            false,
            $paths['public'],
            $paths['private'],
            $failedPublic,
            $failedPrivate,
        );
    }

    /**
     * @return array{public: list<string>, private: list<string>}
     */
    private function matchingPaths(ArticleType $articleType): array
    {
        return [
            'public'  => $this->publicPathsForType($articleType->code),
            'private' => $this->privatePathsForType($articleType),
        ];
    }

    /**
     * @return list<string>
     */
    private function publicPathsForType(string $type): array
    {
        // Paths live under sites/{host}/{locale}/{type}/... — which is exactly
        // what the old '#^sites/[^/]+/[^/]+/{type}/#' regex over a full-bucket
        // listing described. Enumerating hosts × locales lets S3 do the
        // filtering as a prefix.
        $paths = [];

        foreach (Site::getCachedDomains() as $host) {
            if (!$host) {
                continue;
            }

            foreach ($this->locales() as $locale) {
                $paths = array_merge(
                    $paths,
                    $this->publicDisk->allFiles(sitePath($host, "{$locale}/{$type}"))
                );
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return list<string>
     */
    private function privatePathsForType(ArticleType $articleType): array
    {
        $privatePaths = [];

        Article::withoutGlobalScopes()
            ->where('type_id', $articleType->getKey())
            ->with('translations:id,article_id,locale')
            ->select(['id', 'type_id'])
            ->chunkById(500, function ($articles) use (&$privatePaths): void {
                foreach ($articles as $article) {
                    foreach ($article->translations as $translation) {
                        // No exists() — that was one HTTP HEAD per translation.
                        // Deleting a key that isn't there is a no-op on S3.
                        $privatePaths[] = brotliPath(restPath($translation->locale, $article->id));
                    }
                }
            });

        return array_values(array_unique($privatePaths));
    }

    /**
     * @return list<string>
     */
    private function locales(): array
    {
        $locales = Locale::getAvailableAsArr('prefix');

        if ($locales === []) {
            $locales = config('app.locales', ['uk', 'ru', 'en']);
        }

        return array_values(array_unique(array_filter($locales)));
    }

    private function articleType(string $type): ArticleType
    {
        $articleType = ArticleType::query()
            ->where('code', $type)
            ->first();

        if (!$articleType) {
            throw new InvalidArgumentException(sprintf(
                'Unknown article static invalidation type [%s]. Available types: %s.',
                $type,
                implode(', ', $this->types())
            ));
        }

        return $articleType;
    }
}
