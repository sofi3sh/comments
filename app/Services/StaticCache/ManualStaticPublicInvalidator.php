<?php

namespace App\Services\StaticCache;

use App\Models\Settings\Locale;
use App\Models\Site\Site;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Диск — S3, поэтому здесь нет ни одного allFiles() по всему бакету: раньше
 * это был полный рекурсивный листинг на каждый тип (для `all` — девять раз).
 *
 * Точные шаблоны ({locale}/category.br, robots.txt.br, ...) собираются из
 * доменов и локалей и удаляются вслепую: DeleteObject по несуществующему ключу
 * в S3 успешен, так что проверять существование незачем. Их количество в
 * отчёте — это количество кандидатов, а не реально существовавших файлов.
 *
 * Префиксные шаблоны ({locale}/tag/, sitemaps/, ...) без листинга не обойтись —
 * ключи заранее неизвестны, — но листинг теперь ограничен префиксом.
 */
class ManualStaticPublicInvalidator
{
    public const TYPE_ALL = 'all';
    public const TYPE_CATEGORIES = 'categories';
    public const TYPE_TAGS = 'tags';
    public const TYPE_COLLECTIONS = 'collections';
    public const TYPE_DOSSIERS = 'dossiers';
    public const TYPE_PERSON = 'person';
    public const TYPE_COMPANY = 'company';
    public const TYPE_CONTRIBUTORS = 'contributors';
    public const TYPE_EDITORS_LIST = 'editors-list';
    public const TYPE_SEO = 'seo';

    private const DISK = 'static-public';

    private Filesystem $disk;

    public function __construct(private readonly StaticDiskDeleter $deleter)
    {
        $this->disk = Storage::disk(self::DISK);
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return [
            self::TYPE_ALL,
            self::TYPE_CATEGORIES,
            self::TYPE_TAGS,
            self::TYPE_COLLECTIONS,
            self::TYPE_DOSSIERS,
            self::TYPE_PERSON,
            self::TYPE_COMPANY,
            self::TYPE_CONTRIBUTORS,
            self::TYPE_EDITORS_LIST,
            self::TYPE_SEO,
        ];
    }

    public function preview(string $type): ManualStaticInvalidationResult
    {
        return $this->invalidate($type, true);
    }

    /**
     * @return array<string, int>
     */
    public function previewCounts(string $type): array
    {
        $this->assertKnownType($type);

        $types = $type === self::TYPE_ALL
            ? array_values(array_diff($this->types(), [self::TYPE_ALL]))
            : [$type];

        $counts = [];

        foreach ($types as $singleType) {
            $paths = $this->matchingPaths($singleType);
            $counts[$singleType] = count($paths['listed']) + count($paths['blind']);
        }

        return $counts;
    }

    public function invalidate(string $type, bool $dryRun = false): ManualStaticInvalidationResult
    {
        $this->assertKnownType($type);

        ['listed' => $listed, 'blind' => $blind] = $this->matchingPaths($type);
        $paths = array_values(array_unique(array_merge($listed, $blind)));

        if ($dryRun || $paths === []) {
            return new ManualStaticInvalidationResult($type, $dryRun, $paths, [], $blind);
        }

        $failed = $this->deleter->delete(self::DISK, $paths);

        return new ManualStaticInvalidationResult($type, false, $paths, $failed, $blind);
    }

    /**
     * `listed` — реально существующие ключи, найденные листингом по префиксу.
     * `blind` — кандидаты по точным шаблонам, удаляются без проверки.
     *
     * @return array{listed: list<string>, blind: list<string>}
     */
    private function matchingPaths(string $type): array
    {
        $matchers = $this->matchersFor($type);
        $hosts = $this->hosts();

        $listed = [];
        $blind = [];

        foreach ($hosts as $host) {
            foreach ($matchers['exact'] as $tail) {
                $blind[] = sitePath($host, $tail);
            }

            foreach ($matchers['prefixes'] as $prefix) {
                // allFiles() отдаёт пути от корня диска, включая сам префикс.
                $listed = array_merge(
                    $listed,
                    $this->disk->allFiles(rtrim(sitePath($host, $prefix), '/'))
                );
            }
        }

        return [
            'listed' => array_values(array_unique($listed)),
            'blind' => array_values(array_unique($blind)),
        ];
    }

    /**
     * @return list<string>
     */
    private function hosts(): array
    {
        return array_values(array_unique(array_filter(Site::getCachedDomains())));
    }

    /**
     * @return array{exact: list<string>, prefixes: list<string>}
     */
    private function matchersFor(string $type): array
    {
        $types = $type === self::TYPE_ALL
            ? array_values(array_diff($this->types(), [self::TYPE_ALL]))
            : [$type];

        $exact = [];
        $prefixes = [];

        foreach ($this->locales() as $locale) {
            foreach ($types as $singleType) {
                $patterns = $this->patternsForType($singleType, $locale);
                $exact = array_merge($exact, $patterns['exact']);
                $prefixes = array_merge($prefixes, $patterns['prefixes']);
            }
        }

        return [
            'exact' => array_values(array_unique($exact)),
            'prefixes' => array_values(array_unique($prefixes)),
        ];
    }

    /**
     * @return array{exact: list<string>, prefixes: list<string>}
     */
    private function patternsForType(string $type, string $locale): array
    {
        return match ($type) {
            self::TYPE_CATEGORIES => [
                'exact' => [
                    "{$locale}/category.br",
                ],
                'prefixes' => [
                    "{$locale}/category/",
                ],
            ],
            self::TYPE_TAGS => [
                'exact' => [],
                'prefixes' => [
                    "{$locale}/tag/",
                ],
            ],
            self::TYPE_COLLECTIONS => [
                'exact' => [],
                'prefixes' => [
                    "{$locale}/collection/",
                ],
            ],
            self::TYPE_DOSSIERS => [
                'exact' => [
                    "{$locale}/dossier.br",
                ],
                'prefixes' => [
                    "{$locale}/dossier/",
                ],
            ],
            self::TYPE_PERSON => [
                'exact' => [],
                'prefixes' => [
                    "{$locale}/person/",
                ],
            ],
            self::TYPE_COMPANY => [
                'exact' => [],
                'prefixes' => [
                    "{$locale}/company/",
                ],
            ],
            self::TYPE_CONTRIBUTORS => [
                'exact' => [],
                'prefixes' => [
                    "{$locale}/author/",
                    "{$locale}/editor/",
                ],
            ],
            self::TYPE_EDITORS_LIST => [
                'exact' => [
                    "{$locale}/editors.br",
                ],
                'prefixes' => [],
            ],
            // SEO files are not locale-scoped; the same patterns are emitted
            // for every locale and deduplicated by matchersFor().
            self::TYPE_SEO => [
                'exact' => [
                    'robots.txt.br',
                    'sitemap.xml.br',
                ],
                'prefixes' => [
                    'sitemaps/',
                ],
            ],
            default => [
                'exact' => [],
                'prefixes' => [],
            ],
        };
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

    private function assertKnownType(string $type): void
    {
        if (!in_array($type, $this->types(), true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown manual static invalidation type [%s]. Available types: %s.',
                $type,
                implode(', ', $this->types())
            ));
        }
    }
}
