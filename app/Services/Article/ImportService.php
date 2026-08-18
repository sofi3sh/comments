<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\Category;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Translate\CategoryTranslation;
use App\Models\Seo\Translate\SeoMetaTranslation;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImportService
{
    public const OLD_COMMENTS       = 'oldcommentar';
    public const PUBLICATIONS_TABLE = 'publications';
    public const OPINIONSNEW_TABLE  = 'opinionsnew';
    public const OPINIONS_TABLE     = 'opinions';
    public const PERSONS_TABLE      = 'persons';
    public const COMPANY_TABLE      = 'company';
    protected bool $showDebug = false;
    protected bool $showProgress = true;
    protected bool $showReport = true;
    protected bool $showError = true;
    protected array $usersMap = [];
    protected int $defaultSiteId = 1;
    protected array $sourceArticleTypeMap = [
        self::PUBLICATIONS_TABLE => [1, 2, 3],
        self::OPINIONSNEW_TABLE  => [6],
        self::OPINIONS_TABLE     => [6],
        self::PERSONS_TABLE      => [4],
        self::COMPANY_TABLE      => [5],
    ];
    protected const MARKERS = [
        1, // actual
        2, // important
        3, // PR
        4, // exclusive
        5, // partner news
        6, // inside
        7, // fast news
    ];

    private ?User $defaultAuthor = null;
    private const CACHE_PREFIX = 'article_import_';
    private const CACHE_TTL = 86400; // 24 hours
    private array $categoryIdMap = [];
    private array $subCategoryIdMap = [];

    private function info(string $message, array $data = []): void
    {
        if (app()->runningInConsole()) {
            $dataStr = !empty($data) ? ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE) : '';
            echo $message . $dataStr . PHP_EOL;
        }
    }

    private function getTypeIdsByTableName(string $sourceTable)
    {
        return $this->sourceArticleTypeMap[$sourceTable];
    }

    public function fetchPublicationsFromDb(?int $lastId = null, int $limit = 100, string $tableName = 'publications'): array
    {
        $this->showDebug && $this->info('[IMPORT] fetchPublicationsFromDb called', [
            'last_id' => $lastId,
            'limit' => $limit,
            'table_name' => $tableName,
        ]);

        $startTime = microtime(true);

        try {
            $oldDb = DB::connection(self::OLD_COMMENTS);

            $allowedTables = [
                self::PUBLICATIONS_TABLE,
                self::OPINIONSNEW_TABLE,
                self::OPINIONS_TABLE,
            ];
            if (!in_array($tableName, $allowedTables)) {
                throw new \InvalidArgumentException("Invalid table name: {$tableName}");
            }
            // Check which fields exist in the table
            // opinionsnew doesn't have meta_desc_en, source, news_marker, inside, and fast_news fields
            $isPublication = $tableName === self::PUBLICATIONS_TABLE;
            // Build query with explicit field selection to avoid conflicts
            // Select only needed fields from publications table
            // Note: dict_subtypes has only 'name' field (not name_ua/ru/en)
            // Note: opinionsnew doesn't have meta_desc_en, source, news_marker, inside, and fast_news fields
            $metaDescEnField = $isPublication ? 'p.meta_desc_en' : 'NULL as meta_desc_en';
            $sourceField     = $isPublication ? 'p.source' : 'NULL as source';
            $newsMarkerField = $isPublication ? 'p.news_marker' : 'NULL as news_marker';
            $insideField     = $isPublication ? 'p.inside' : 'NULL as inside';
            $fastNewsField   = $isPublication ? 'p.fast_news' : 'NULL as fast_news';
            $authorId        = $isPublication ? 'p.adder' : 'p.author_id';

            $sql = /** @lang text */
                "SELECT 
                        p.id,
                        p.date,
                        p.add_date,
                        p.last_edit,
                        p.title_ua,
                        p.title_ru,
                        p.title_en,
                        p.content_ua,
                        p.content_ru,
                        p.content_en,
                        p.anons_ua,
                        p.anons_ru,
                        p.anons_en,
                        {$sourceField},
                        p.views,
                        p.meta_ua,
                        p.meta_ru,
                        p.meta_en,
                        p.meta_desc_ua,
                        p.meta_desc_ru,
                        {$metaDescEnField},
                        p.translit_ua,
                        p.translit_ru,
                        p.translit_en,
                        p.category_id,
                        p.sub_id,
                        p.sub_type,
                        {$newsMarkerField},
                        p.exclusive,
                        {$insideField},
                        {$fastNewsField},
                        p.tosite,
                        {$authorId} as author_id,
                        p.last_editor,
                        c.id as category_dict_id,
                        c.name_ua as category_name_ua,
                        c.name_ru as category_name_ru,
                        c.name_en as category_name_en,
                        st.id as subtype_dict_id,
                        st.name as subtype_name
                    FROM (
                        SELECT id
                        FROM `{$tableName}`
                        WHERE id > ?
                          AND tosite IN (1, 2)
                        ORDER BY id ASC
                        LIMIT ?
                    ) ids
                    JOIN `{$tableName}` p ON p.id = ids.id
                    LEFT JOIN dict_category c ON (c.id = p.category_id) 
                    LEFT JOIN dict_subtypes st ON (st.id = p.sub_type)";

            $whereId = $lastId !== null ? $lastId : 0;

            // Log filtered count only for first batch (when lastId is null or 0)
            if ($lastId === null || $lastId === 0) {
                // Get total count before filtering tosite = 0
                $countSql = /** @lang text */"SELECT COUNT(*) as total FROM `{$tableName}` p";
                $totalBeforeFilter = $oldDb->selectOne($countSql);
                $totalCountBeforeFilter = $totalBeforeFilter->total ?? 0;

                // Get count with tosite filter
                $countSqlFiltered = /** @lang text */"SELECT COUNT(*) as total 
                                    FROM `{$tableName}` p 
                                    WHERE p.tosite IN (1, 2)";
                $totalAfterFilter = $oldDb->selectOne($countSqlFiltered);
                $totalCountAfterFilter = $totalAfterFilter->total ?? 0;
                $filteredOut = $totalCountBeforeFilter - $totalCountAfterFilter;

                $this->showDebug && $this->info('[IMPORT] [FETCH] Статистика публікацій (тільки для першого батчу)', [
                    'table_name' => $tableName,
                    'total_before_filter' => $totalCountBeforeFilter,
                    'total_after_filter_tosite' => $totalCountAfterFilter,
                    'filtered_out_tosite_0' => $filteredOut,
                ]);
            }

            $publications = $oldDb->select($sql, [$whereId, $limit]);

            $this->showDebug && $this->info('[IMPORT] [FETCH] Отримано публікацій з БД', [
                'table_name' => $tableName,
                'last_id' => $lastId,
                'where_id' => $whereId,
                'limit' => $limit,
                'publications_returned' => count($publications),
            ]);

            // Convert to array format compatible with existing code
            $result = [];
            foreach ($publications as $pub) {
                // Convert stdClass to array
                $pubArray = json_decode(json_encode($pub), true);

                // Add table name to identify source
                $pubArray['_source_table'] = $tableName;

                // FIX: opinionsnew always maps sub_type to 6
                if ($tableName === self::OPINIONSNEW_TABLE) {
                    $pubArray['sub_type'] = 6;
                }

                // Ensure all fields from publications table are available
                // Join might create duplicate keys, so we prioritize p.* fields
                $result[] = $pubArray;
            }

            $totalTime = round((microtime(true) - $startTime), 2);

            $this->showDebug && $this->info('[IMPORT] ===== Отримання публікацій з БД завершено =====', [
                'table_name' => $tableName,
                'publications_found' => count($result),
                'last_id' => $lastId,
                'total_time_sec' => $totalTime,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->showDebug && $this->info('[IMPORT] Помилка отримання публікацій з БД', [
                'table_name' => $tableName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch opinions from old database using SQL query
     *
     * @param int|null $lastId
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function fetchOpinionsFromDb(?int $lastId = null, int $limit = 100): array
    {
        $tableName = self::OPINIONS_TABLE;

        $this->showDebug && $this->info('[IMPORT] fetchOpinionsFromDb called', [
            'last_id' => $lastId,
            'limit' => $limit,
            'table_name' => $tableName,
        ]);

        $startTime = microtime(true);

        try {
            $oldDb = DB::connection(self::OLD_COMMENTS);

            $sql = /** @lang text */
                "SELECT 
                        p.id,
                        p.date,
                        p.title,
                        p.content,
                        p.anons,
                        p.views,
                        p.translit,
                        p.tosite,
                        p.author_id
                        FROM (
                        SELECT id
                        FROM `{$tableName}`
                        WHERE id > ?
                          AND tosite IN (1, 2)
                        ORDER BY id ASC
                        LIMIT ?
                    ) ids
                    JOIN `{$tableName}` p ON p.id = ids.id";

            $whereId = $lastId !== null ? $lastId : 0;

            // Log filtered count only for first batch (when lastId is null or 0)
            if ($lastId === null || $lastId === 0) {
                // Get total count before filtering tosite = 0
                $countSql = /** @lang text */"SELECT COUNT(*) as total FROM `{$tableName}` p";
                $totalBeforeFilter = $oldDb->selectOne($countSql);
                $totalCountBeforeFilter = $totalBeforeFilter->total ?? 0;

                // Get count with tosite filter
                $countSqlFiltered = /** @lang text */"SELECT COUNT(*) as total 
                                    FROM `{$tableName}` p 
                                    WHERE p.tosite IN (1, 2)";
                $totalAfterFilter = $oldDb->selectOne($countSqlFiltered);
                $totalCountAfterFilter = $totalAfterFilter->total ?? 0;
                $filteredOut = $totalCountBeforeFilter - $totalCountAfterFilter;

                $this->showDebug && $this->info('[IMPORT] [FETCH] Статистика ДУМОК (тільки для першого батчу)', [
                    'table_name' => $tableName,
                    'total_before_filter' => $totalCountBeforeFilter,
                    'total_after_filter_tosite' => $totalCountAfterFilter,
                    'filtered_out_tosite_0' => $filteredOut,
                ]);
            }

            $publications = $oldDb->select($sql, [$whereId, $limit]);

            $this->showDebug && $this->info('[IMPORT] [FETCH] Отримано ДУМОК з БД', [
                'table_name' => $tableName,
                'last_id' => $lastId,
                'where_id' => $whereId,
                'limit' => $limit,
                'publications_returned' => count($publications),
            ]);

            // Convert to array format compatible with existing code
            $result = [];
            foreach ($publications as $pub) {
                $pubArray = json_decode(json_encode($pub), true);
                $pubArray['_source_table'] = $tableName;

                $pubArray['sub_type'] = 6;
                $result[] = $pubArray;
            }

            $totalTime = round((microtime(true) - $startTime), 2);

            $this->showDebug && $this->info('[IMPORT] ===== Отримання ДУМОК з БД завершено =====', [
                'table_name' => $tableName,
                'publications_found' => count($result),
                'last_id' => $lastId,
                'total_time_sec' => $totalTime,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->showDebug && $this->info('[IMPORT] Помилка отримання ДУМКИ з БД', [
                'table_name' => $tableName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch persons from old database using SQL query
     *
     * @param int|null $lastId Last processed person ID (for pagination)
     * @param int $limit Number of persons to fetch per batch
     * @return array
     */
    public function fetchPersonsFromDb(?int $lastId = null, int $limit = 100): array
    {
        $this->showDebug && $this->info('[IMPORT] fetchPersonsFromDb called', [
            'last_id' => $lastId,
            'limit' => $limit,
        ]);

        $startTime = microtime(true);

        try {
            $oldDb = DB::connection(self::OLD_COMMENTS);

            $sql = /** @lang text */
                "SELECT 
                        id,
                        name_ru,
                        name_ua,
                        date,
                        add_date,
                        content_ru,
                        content_ua,
                        translit_ru,
                        translit_ua,
                        views
                    FROM `persons`
                    WHERE id > ?
                    ORDER BY id ASC 
                    LIMIT ?";

            $whereId = $lastId !== null ? $lastId : 0;

            // Log count only for first batch (when lastId is null or 0)
            if ($lastId === null || $lastId === 0) {
                $countSql = "SELECT COUNT(*) as total FROM `persons`";
                $totalCount = $oldDb->selectOne($countSql);
                $total = $totalCount->total ?? 0;

                $this->showDebug && $this->info('[IMPORT] [FETCH] Статистика persons (тільки для першого батчу)', [
                    'total' => $total,
                ]);
            }

            $persons = $oldDb->select($sql, [$whereId, $limit]);

            $this->showDebug && $this->info('[IMPORT] [FETCH] Отримано persons з БД', [
                'last_id' => $lastId,
                'where_id' => $whereId,
                'limit' => $limit,
                'persons_returned' => count($persons),
            ]);

            $result = [];
            foreach ($persons as $person) {
                // Convert stdClass to array
                $personArray = json_decode(json_encode($person), true);

                $personArray['_source_table'] = 'persons';

                $result[] = $personArray;
            }

            $totalTime = round((microtime(true) - $startTime), 2);

            $this->showDebug && $this->info('[IMPORT] ===== Отримання persons з БД завершено =====', [
                'persons_found' => count($result),
                'last_id' => $lastId,
                'total_time_sec' => $totalTime,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->showDebug && $this->info('[IMPORT] Помилка отримання persons з БД', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch companies from old database using SQL query
     *
     * @param int|null $lastId Last processed company ID (for pagination)
     * @param int $limit Number of companies to fetch per batch
     * @return array
     */
    public function fetchCompaniesFromDb(?int $lastId = null, int $limit = 100): array
    {
        $this->showDebug && $this->info('[IMPORT] fetchCompaniesFromDb called', [
            'last_id' => $lastId,
            'limit' => $limit,
        ]);

        $startTime = microtime(true);

        try {
            $oldDb = DB::connection(self::OLD_COMMENTS);

            $sql = /** @lang text */
                "SELECT 
                        id,
                        name_ru,
                        name_ua,
                        date,
                        content_ru,
                        content_ua,
                        translit_ru,
                        translit_ua
                    FROM `company`
                    WHERE id > ?
                    ORDER BY id ASC 
                    LIMIT ?";

            $whereId = $lastId !== null ? $lastId : 0;

            if ($lastId === null || $lastId === 0) {
                $countSql = "SELECT COUNT(*) as total FROM `company`";
                $totalCount = $oldDb->selectOne($countSql);
                $total = $totalCount->total ?? 0;

                $this->showDebug && $this->info('[IMPORT] [FETCH] Статистика company (тільки для першого батчу)', [
                    'total' => $total,
                ]);
            }

            $companies = $oldDb->select($sql, [$whereId, $limit]);

            $this->showDebug && $this->info('[IMPORT] [FETCH] Отримано company з БД', [
                'last_id' => $lastId,
                'where_id' => $whereId,
                'limit' => $limit,
                'companies_returned' => count($companies),
            ]);

            $result = [];
            foreach ($companies as $company) {
                $companyArray = json_decode(json_encode($company), true);

                $companyArray['_source_table'] = 'company';

                $result[] = $companyArray;
            }

            $totalTime = round((microtime(true) - $startTime), 2);

            $this->showDebug && $this->info('[IMPORT] ===== Отримання company з БД завершено =====', [
                'companies_found' => count($result),
                'last_id' => $lastId,
                'total_time_sec' => $totalTime,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->showDebug && $this->info('[IMPORT] Помилка отримання company з БД', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Convert persons/company data to publication format for importSingleArticle
     *
     * @param array $data Data from persons or company table
     * @return array Converted data in publication format
     */
    private function convertToPublicationFormat(array $data): array
    {
        $sourceTable = $data['_source_table'] ?? 'persons';

        $converted = [
            'id' => $data['id'] ?? null,
            'date' => $data['date'] ?? null,
            'add_date' => $data['add_date'] ?? null,
            'title_ua' => $data['name_ua'] ?? null,
            'title_ru' => $data['name_ru'] ?? null,
            'title_en' => null,
            'content_ua' => $data['content_ua'] ?? null,
            'content_ru' => $data['content_ru'] ?? null,
            'content_en' => null,
            'anons_ua' => null,
            'anons_ru' => null,
            'anons_en' => null,
            'source' => null,
            'views' => isset($data['views']) && $data['views'] !== null ? (int)$data['views'] : 0,
            'meta_ua' => null,
            'meta_ru' => null,
            'meta_en' => null,
            'meta_desc_ua' => null,
            'meta_desc_ru' => null,
            'meta_desc_en' => null,
            'translit_ua' => $data['translit_ua'] ?? null,
            'translit_ru' => $data['translit_ru'] ?? null,
            'translit_en' => null,
            'category_id' => null,
            'sub_type' => null,
            'news_marker' => null,
            'exclusive' => 0,
            'inside' => 0,
            'fast_news' => 0,
            'tosite' => null,
            'category_dict_id' => null,
            'category_name_ua' => null,
            'category_name_ru' => null,
            'category_name_en' => null,
            'subtype_dict_id' => null,
            'subtype_name' => null,
            '_source_table' => $sourceTable,
        ];

        if ($sourceTable === 'opinions') {
            $converted['title_ua']    = $data['title'] ?? null;
            $converted['content_ua']  = $data['content'] ?? null;
            $converted['anons_ua']    = $data['anons'] ?? null;
            $converted['translit_ua'] = $data['translit'] ?? null;
            $converted['sub_type']    = 6;
        }

        if ($sourceTable === 'persons') {
            $converted['sub_type'] = 4;
            $converted['date'] = $converted['add_date'] ?? '';
        }

        if ($sourceTable === 'company') {
            $converted['views'] = 0;
            $converted['sub_type'] = 5;
            $converted['date'] = now();
            $converted['add_date'] = now();
        }

        return $converted;
    }

    /**
     * Fetch dictionary tables from old database
     *
     * @return array
     */
    public function fetchDictTablesFromDb(): array
    {
        $this->showDebug && $this->info('[IMPORT] fetchDictTablesFromDb called');

        $startTime = microtime(true);

        try {
            // Try to use cached dict tables first
            $cacheCheckStart = microtime(true);
            $dictCategories = $this->getCachedDictTable('dict_category');
            $dictSubCategories = $this->getCachedDictTable('dict_sub_category');
            $dictSubtypes = $this->getCachedDictTable('dict_subtypes');
            $cacheCheckTime = round((microtime(true) - $cacheCheckStart) * 1000, 2);

            $this->showDebug && $this->info('[IMPORT] Перевірка кешу словників', [
                'dict_category_cached' => $dictCategories !== null,
                'dict_sub_category_cached' => $dictSubCategories !== null,
                'dict_subtypes_cached' => $dictSubtypes !== null,
                'time_ms' => $cacheCheckTime,
            ]);

            // If all dict tables are cached, return them
            if ($dictCategories !== null && $dictSubCategories !== null && $dictSubtypes !== null) {
                $this->showDebug && $this->info('[IMPORT] Використання кешованих словників');
                return [
                    'dict_category' => $dictCategories,
                    'dict_sub_category' => $dictSubCategories,
                    'dict_subtypes' => $dictSubtypes,
                ];
            }

            // Fetch from database
            $oldDb = DB::connection(self::OLD_COMMENTS);

            // Fetch dict_category
            if ($dictCategories === null) {
                $fetchStart = microtime(true);
                $dictCategories = $oldDb->table('dict_category')
                    ->get()
                    ->map(function($item) {
                        return (array)$item;
                    })
                    ->toArray();
                $fetchTime = round((microtime(true) - $fetchStart) * 1000, 2);
                $this->showDebug && $this->info('[IMPORT] Отримано dict_category', [
                    'count' => count($dictCategories),
                    'time_ms' => $fetchTime,
                ]);
                $this->cacheDictTable('dict_category', $dictCategories);
            }

            // Fetch dict_sub_category
            if ($dictSubCategories === null) {
                $fetchStart = microtime(true);
                $dictSubCategories = $oldDb->table('dict_sub_category')
                    ->get()
                    ->map(function($item) {
                        return (array)$item;
                    })
                    ->toArray();
                $fetchTime = round((microtime(true) - $fetchStart) * 1000, 2);
                $this->showDebug && $this->info('[IMPORT] Отримано dict_sub_category', [
                    'count' => count($dictSubCategories),
                    'time_ms' => $fetchTime,
                ]);
                $this->cacheDictTable('dict_sub_category', $dictSubCategories);
            }

            $totalTime = round((microtime(true) - $startTime), 2);

            $this->showDebug && $this->info('[IMPORT] ===== Отримання словників з БД завершено =====', [
                'dict_category_count' => count($dictCategories),
                'dict_sub_category_count' => count($dictSubCategories),
                'total_time_sec' => $totalTime,
            ]);

            return [
                'dict_category' => $dictCategories,
                'dict_sub_category' => $dictSubCategories,
            ];

        } catch (\Exception $e) {
            $this->showDebug && $this->info('[IMPORT] Помилка отримання словників з БД', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get cached dict table data
     *
     * @param string $tableName
     * @return array|null
     */
    private function getCachedDictTable(string $tableName): ?array
    {
        $cacheKey = self::CACHE_PREFIX . $tableName;
        return Cache::get($cacheKey);
    }

    /**
     * Cache dict table data
     *
     * @param string $tableName
     * @param array $data
     * @return void
     */
    private function cacheDictTable(string $tableName, array $data): void
    {
        $cacheKey = self::CACHE_PREFIX . $tableName;
        Cache::put($cacheKey, $data, self::CACHE_TTL);
    }

    /**
     * Import categories and subcategories from old dictionary tables into new categories table
     * and build mapping arrays from old IDs to new category IDs
     *
     * @param array $dictCategories
     * @param array $dictSubCategories
     * @return void
     */
    private function buildCategoryMappings(array $dictCategories, array $dictSubCategories): void
    {
        $startTime = microtime(true);
        $created = 0;
        $existing = 0;

        $this->categoryIdMap = [];
        $this->subCategoryIdMap = [];

        $reservedSlugs = [];
        foreach ($dictCategories as $row) {
            $dir = isset($row['dir']) ? trim((string)$row['dir']) : (isset($row[4]) ? trim((string)$row[4]) : '');
            if ($dir !== '') {
                $reservedSlugs[$dir] = true;
            }
        }

        // First import top-level categories from dict_category
        foreach ($dictCategories as $row) {
            if (isset($row['id'])) {
                // Associative array
                $oldId = $row['id'];
                $nameUa = $row['name_ua'] ?? '';
                $nameRu = $row['name_ru'] ?? '';
                $nameEn = $row['name_en'] ?? '';
                $titleUa = $row['title_ua'] ?? '';
                $titleRu = $row['title_ru'] ?? '';
                $titleEn = $row['title_en'] ?? '';
                $descriptionUa = $row['description_ua'] ?? '';
                $descriptionRu = $row['description_ru'] ?? '';
                $descriptionEn = $row['description_en'] ?? '';
                $keywordsUa = $row['keywords_ua'] ?? '';
                $keywordsRu = $row['keywords_ru'] ?? '';
                $keywordsEn = $row['keywords_en'] ?? '';
                $slugFromDict = $row['dir'] ?? null;
            } else {
                if (count($row) < 2) {
                    continue;
                }
                $oldId = $row[0];
                $nameUa = $row[1] ?? '';
                $nameRu = $row[2] ?? '';
                $nameEn = $row[3] ?? '';
                $titleUa = $row[5] ?? '';
                $titleRu = $row[6] ?? '';
                $titleEn = $row[7] ?? '';
                $descriptionUa = $row[11] ?? '';
                $descriptionRu = $row[12] ?? '';
                $descriptionEn = $row[13] ?? '';
                $keywordsUa = $row[14] ?? '';
                $keywordsRu = $row[15] ?? '';
                $keywordsEn = $row[16] ?? '';
                $slugFromDict = $row[4] ?? null;
            }

            if (!$oldId) {
                continue;
            }

            $slug = ($slugFromDict !== null && trim((string)$slugFromDict) !== '')
                ? trim((string)$slugFromDict)
                : null;
            if ($slug === null) {
                $slug = Str::slug($nameUa ?: $nameRu ?: $nameEn);
                if ($slug === '') {
                    $slug = null;
                }
                $baseSlug = $slug;
                $counter = 2;
                while ($slug !== null && (isset($reservedSlugs[$slug]) || (Category::where('slug', $slug)->whereNull('parent_id')->exists()))) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Try to find existing category by slug and parent_id = null
            $category = null;
            if ($slug) {
                $category = Category::where('slug', $slug)
                    ->whereNull('parent_id')
                    ->first();
            }

            if (!$category) {
                $category = $this->findOrCreateCategory(
                    $nameUa,
                    $nameRu,
                    $nameEn,
                    $titleUa,
                    $titleRu,
                    $titleEn,
                    $descriptionUa,
                    $descriptionRu,
                    $descriptionEn,
                    $keywordsUa,
                    $keywordsRu,
                    $keywordsEn,
                    null,
                    $slug,
                    false
                );
                $created++;
            } else {
                $this->syncCategoryTranslation($category, 'uk', $nameUa);
                $this->syncCategoryTranslation($category, 'ru', $nameRu);
                $this->syncCategoryTranslation($category, 'en', $nameEn);
                $this->syncCategorySeoMeta($category, 'uk', $titleUa, $descriptionUa, $keywordsUa);
                $this->syncCategorySeoMeta($category, 'ru', $titleRu, $descriptionRu, $keywordsRu);
                $this->syncCategorySeoMeta($category, 'en', $titleEn, $descriptionEn, $keywordsEn);
                $existing++;
            }

            $this->categoryIdMap[(int)$oldId] = $category->id;
        }

        // Then import subcategories from dict_sub_category
        $subCreated = 0;
        $subExisting = 0;
        foreach ($dictSubCategories as $row) {
            if (isset($row['id'])) {
                // Associative array
                $oldSubId = $row['id'];
                $parentOldId = $row['parent_id'] ?? null;
                $nameUa = $row['name_ua'] ?? '';
                $nameRu = $row['name_ru'] ?? '';
                $nameEn = $row['name_en'] ?? '';
                $titleUa = $row['title_ua'] ?? '';
                $titleRu = $row['title_ru'] ?? '';
                $titleEn = $row['title_en'] ?? '';
                $descriptionUa = '';
                $descriptionRu = '';
                $descriptionEn = '';
                $keywordsUa = '';
                $keywordsRu = '';
                $keywordsEn = '';
                $slugFromDict = $row['dir'] ?? null;
            } else {
                // Indexed array (fallback)
                if (count($row) < 3) {
                    continue;
                }
                $oldSubId = $row[0];
                $parentOldId = $row[1] ?? null;
                $nameUa = $row[2] ?? '';
                $nameRu = $row[3] ?? '';
                $nameEn = $row[4] ?? '';
                $titleUa = $row[6] ?? '';
                $titleRu = $row[7] ?? '';
                $titleEn = $row[8] ?? '';
                $descriptionUa = '';
                $descriptionRu = '';
                $descriptionEn = '';
                $keywordsUa = '';
                $keywordsRu = '';
                $keywordsEn = '';
                $slugFromDict = $row[5] ?? null;
            }

            if (!$oldSubId || !$parentOldId) {
                continue;
            }

            $parentOldId = (int)$parentOldId;
            $parentNewId = $this->categoryIdMap[$parentOldId] ?? null;
            if (!$parentNewId) {
                continue;
            }

            $slug = ($slugFromDict !== null && trim((string)$slugFromDict) !== '')
                ? trim((string)$slugFromDict)
                : null;
            if ($slug === null) {
                $slug = Str::slug($nameUa ?: $nameRu ?: $nameEn);
                if ($slug === '') {
                    $slug = null;
                }
            }

            $subcategory = null;
            if ($slug) {
                $subcategory = Category::where('slug', $slug)
                    ->where('parent_id', $parentNewId)
                    ->first();
            }

            if (!$subcategory) {
                $subcategory = $this->findOrCreateCategory(
                    $nameUa,
                    $nameRu,
                    $nameEn,
                    $titleUa,
                    $titleRu,
                    $titleEn,
                    $descriptionUa,
                    $descriptionRu,
                    $descriptionEn,
                    $keywordsUa,
                    $keywordsRu,
                    $keywordsEn,
                    $parentNewId,
                    $slug,
                    false
                );
                $subCreated++;
            } else {
                $this->syncCategoryTranslation($subcategory, 'uk', $nameUa);
                $this->syncCategoryTranslation($subcategory, 'ru', $nameRu);
                $this->syncCategoryTranslation($subcategory, 'en', $nameEn);
                $this->syncCategorySeoMeta($subcategory, 'uk', $titleUa, $descriptionUa, $keywordsUa);
                $this->syncCategorySeoMeta($subcategory, 'ru', $titleRu, $descriptionRu, $keywordsRu);
                $this->syncCategorySeoMeta($subcategory, 'en', $titleEn, $descriptionEn, $keywordsEn);
                $subExisting++;
            }

            $this->subCategoryIdMap[(int)$oldSubId] = $subcategory->id;
        }

        $totalTime = round((microtime(true) - $startTime), 2);

        $this->showDebug && $this->info('[IMPORT] [CATEGORIES] Категорії та підкатегорії імпортовано', [
            'categories_created' => $created,
            'categories_existing' => $existing,
            'subcategories_created' => $subCreated,
            'subcategories_existing' => $subExisting,
            'dict_categories_total' => count($dictCategories),
            'dict_sub_categories_total' => count($dictSubCategories),
            'time_sec' => $totalTime,
        ]);
    }

    /**
     * Find or create category
     *
     * @return Category
     */
    private function findOrCreateCategory(
        string $nameUa,
        string $nameRu,
        string $nameEn,
        string $titleUa,
        string $titleRu,
        string $titleEn,
        string $descriptionUa,
        string $descriptionRu,
        string $descriptionEn,
        string $keywordsUa,
        string $keywordsRu,
        string $keywordsEn,
        ?int $parentId,
        ?string $slugOverride = null,
        bool $generateSlugIfMissing = true
    ): Category
    {

        if ($slugOverride !== null) {
            $slug = $slugOverride;
        } elseif ($generateSlugIfMissing) {
            $slug = Str::slug($nameUa ?: $nameRu ?: $nameEn);
        } else {
            $slug = null;
        }

        $category = null;
        if ($slug !== null) {
            $category = Category::where('slug', $slug)
                ->where('parent_id', $parentId)
                ->first();
        }

        if ($category) {
            $this->syncCategoryTranslation($category, 'uk', $nameUa);
            $this->syncCategoryTranslation($category, 'ru', $nameRu);
            $this->syncCategoryTranslation($category, 'en', $nameEn);
            $this->syncCategorySeoMeta($category, 'uk', $titleUa, $descriptionUa, $keywordsUa);
            $this->syncCategorySeoMeta($category, 'ru', $titleRu, $descriptionRu, $keywordsRu);
            $this->syncCategorySeoMeta($category, 'en', $titleEn, $descriptionEn, $keywordsEn);
            return $category;
        }

        if ($slugOverride === null && ($nameUa || $nameRu || $nameEn)) {
            $query = Category::where('parent_id', $parentId)
                ->whereHas('translations', function($q) use ($nameUa, $nameRu, $nameEn) {
                    if ($nameUa) {
                        $q->where('locale', 'uk')->where('name', $nameUa);
                    } elseif ($nameRu) {
                        $q->where('locale', 'ru')->where('name', $nameRu);
                    } elseif ($nameEn) {
                        $q->where('locale', 'en')->where('name', $nameEn);
                    }
                });

            $category = $query->first();

            if ($category) {
                $this->syncCategoryTranslation($category, 'uk', $nameUa);
                $this->syncCategoryTranslation($category, 'ru', $nameRu);
                $this->syncCategoryTranslation($category, 'en', $nameEn);
                $this->syncCategorySeoMeta($category, 'uk', $titleUa, $descriptionUa, $keywordsUa);
                $this->syncCategorySeoMeta($category, 'ru', $titleRu, $descriptionRu, $keywordsRu);
                $this->syncCategorySeoMeta($category, 'en', $titleEn, $descriptionEn, $keywordsEn);
                return $category;
            }
        }

        $baseSlug = $slug ?: 'category';
        $uniqueSlug = $baseSlug;
        $counter = 1;
        while (Category::where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        if ($slug === null) {
            $generated = Str::slug($nameUa ?: $nameRu ?: $nameEn);
            $slug = $generated !== '' ? $generated : ('category-' . uniqid());
        }

        $category = Category::create([
            'site_id' => 1,
            'parent_id' => $parentId,
            'slug' => $slug,
        ]);

        // Create translations and SEO meta for category
        $this->syncCategoryTranslation($category, 'uk', $nameUa);
        $this->syncCategoryTranslation($category, 'ru', $nameRu);
        $this->syncCategoryTranslation($category, 'en', $nameEn);
        $this->syncCategorySeoMeta($category, 'uk', $titleUa, $descriptionUa, $keywordsUa);
        $this->syncCategorySeoMeta($category, 'ru', $titleRu, $descriptionRu, $keywordsRu);
        $this->syncCategorySeoMeta($category, 'en', $titleEn, $descriptionEn, $keywordsEn);

        return $category;
    }

    private function syncCategoryTranslation(Category $category, string $locale, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $translation = $category->translations()->where('locale', $locale)->first();
        if (!$translation) {
            CategoryTranslation::create([
                'category_id' => $category->id,
                'locale' => $locale,
                'name' => $name,
            ]);

            return;
        }

        if (($translation->name ?? '') === '') {
            $translation->update(['name' => $name]);
        }
    }

    private function syncCategorySeoMeta(
        Category $category,
        string $locale,
        string $title = '',
        string $description = '',
        string $keywords = ''
    ): void
    {
        $payload = [
            'meta_title'       => trim($title),
            'meta_description' => trim($description),
            'meta_keywords'    => trim($keywords),
        ];

        if ($payload['meta_title'] === '' && $payload['meta_description'] === '' && $payload['meta_keywords'] === '') {
            return;
        }

        $seoMeta = $category->seoMeta()->firstOrCreate([]);

        SeoMetaTranslation::updateOrCreate(
            [
                'seo_meta_id' => $seoMeta->id,
                'locale' => $locale,
            ],
            $payload
        );
    }
    public function buildCategoryMappingsFromSlug(array $dictCategories, array $dictSubCategories): array
    {
        $categoryMap = [];
        $subCategoryMap = [];

        // preload categories (важно для производительности)
        $allCategories = Category::all()->groupBy(function ($cat) {
            return ($cat->parent_id ?? 0) . '|' . $cat->slug;
        });

        foreach ($dictCategories as $row) {
            $oldId = $row['id'] ?? $row[0] ?? null;
            $slug = trim((string)($row['dir'] ?? $row[4] ?? ''));

            if (!$oldId || !$slug) continue;

            $key = '0|' . $slug;

            if (isset($allCategories[$key])) {
                $categoryMap[$oldId] = $allCategories[$key]->first()->id;
            }
        }

        foreach ($dictSubCategories as $row) {
            $oldId = $row['id'] ?? $row[0] ?? null;
            $slug = trim((string)($row['dir'] ?? $row[5] ?? ''));
            $parentOldId = $row['parent_id'] ?? $row[1] ?? null;

            if (!$oldId || !$slug || !$parentOldId) continue;

            $parentNewId = $categoryMap[$parentOldId] ?? null;
            if (!$parentNewId) continue;

            $key = $parentNewId . '|' . $slug;

            if (isset($allCategories[$key])) {
                $subCategoryMap[$oldId] = $allCategories[$key]->first()->id;
            }
        }

        return [
            'categories' => $categoryMap,
            'subcategories' => $subCategoryMap,
        ];
    }


//============================== I M P O R T ==============================================

    public function importDicts()
    {
        $dictData = $this->fetchDictTablesFromDb();

        $this->buildCategoryMappings(
            $dictData['dict_category'] ?? [],
            $dictData['dict_sub_category'] ?? []
        );

        $this->importTags();

        return 1;
    }

    public function importUsers()
    {
        DB::table('article_authors')->delete();
        DB::table('users')->delete();

        $processed = 0;
        $inserted = 0;
        $errors = [];
        $batches = 0;

        $oldDb = DB::connection('oldcommentar');
        $now = now();

        $oldDb->table('user')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($now, &$processed, &$inserted) {

                $newUsers = [];
                $userTranslations = [];

                foreach ($rows as $user) {

                    if (!$user->id) { continue; }

                    $processed++;

                    $id = $user->id;
                    $fio = parseFio($user->fio);

                    $newUsers[] = [
                        'id' => $id,
                        'name'         => $fio[0],
                        'surname'      => $fio[1],
                        'email'        => $this->getEmail($user, 'user_'),
                        'phone'        => $user->phone,
                        'avatar'       => $user->photo,
                        'facebook_url' => $user->social_facebook,
                        'linkedin_url' => null,
                        'twitter_url'  => $user->social_twitter,
                        'personal_data_processed' => false,
                        'site_rules_accepted'     => false,
                        'password'     => $user->password_hash,
                        'created_at'   => $this->ts($user->created_at),
                        'updated_at'   => $this->ts($user->updated_at),
                        'deleted_at'   => (int)$user->status !== 10 ? $now : null,
                        'old_id'       => $user->id,
                    ];

                    $userTranslations[] = [
                        'user_id' => $id,
                        'locale'  => 'uk',
                        'bio'     => $user->info,
                        'name'    => $fio[0],
                        'surname' => $fio[01],
                        'position' => $user->position,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }

                if (!empty($newUsers)) {
                    DB::table('users')->insert($newUsers);
                    DB::table('user_translations')->insert($userTranslations);

                    $inserted += count($newUsers);
                }
            });

        return [
            'stats'    => true,
            'total_processed' => $processed,
            'imported' => $inserted,
            'skipped'  => $processed - $inserted,
            'batches'  => $batches,
            'errors'   => $errors, //stat
        ];
    }

    public function importOpinionAuthors()
    {
        $oldDb = DB::connection(self::OLD_COMMENTS);
        $now = now();

        $processed = 0;
        $inserted = 0;
        $errors = [];
        $batches = 0;

        // ИНДИКАТОР
        $spinner = ['|', '/', '-', '\\'];
        $spinIndex = 0;
        $lastRender = microtime(true);

        $oldDb->table('opinions_author')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (
                $now,
                &$processed,
                &$inserted,
                &$batches,
                &$spinner,
                &$spinIndex,
                &$lastRender
            ) {

                $batches++;
                $newUsers = [];
                $translations = [];

                foreach ($rows as $user) {

                    if (!$user->id) {continue;}

                    $fioRaw = $user->fio_ua ?? $user->fio ?? '';
                    [$name, $surname] = parseFio($fioRaw);
                    if (!$name) {
                        $name = 'Author';
                    }
                    if (!$surname) {
                        $surname = 'Author_'.(string)$user->id;
                    }

                    $newUsers[] = [
                        'old_id' => $user->id,
                        'name'   => $name,
                        'surname'=> $surname,
                        'email'  => $this->getEmail($user, 'author_'),
                        'phone'        => null,
                        'avatar'       => $user->image,
                        'facebook_url' => null,
                        'linkedin_url' => null,
                        'twitter_url'  => null,
                        'personal_data_processed' => false,
                        'site_rules_accepted'     => false,
                        'password'     => Hash::make('Demo1234!password'),
                        'created_at'   => $now,
                        'updated_at'   => $now,
                        'deleted_at'   => null,
                    ];

                    $processed++;

                    //  РЕДКИЙ РЕНДЕР (раз в ~100 items или 1 сек)
                    if ($processed % 100 === 0 || (microtime(true) - $lastRender) > 1) {

                        $spinIndex = ($spinIndex + 1) % 4;

                        echo "\r[AUTHORS] {$spinner[$spinIndex]} processed: {$processed}";
                        flush();

                        $lastRender = microtime(true);
                    }
                }

                if (empty($newUsers)) {
                    echo PHP_EOL;
                    return [
                        'stats' => true,
                        'total_processed' => $processed,
                        'imported' => $inserted,
                        'skipped' => $processed - $inserted,
                        'batches' => $batches,
                    ];
                }

                DB::table('users')->insert($newUsers);

                $inserted += count($newUsers);

                $oldIds = array_column($newUsers, 'old_id');

                $userMap = DB::table('users')
                    ->whereIn('old_id', $oldIds)
                    ->pluck('id', 'old_id')
                    ->toArray();

                foreach ($rows as $user) {

                    $userId = $userMap[$user->id] ?? null;
                    if (!$userId) continue;

                    foreach ([
                                 'uk' => ['fio_ua', 'position_ua', 'desc_ua'],
                                 'ru' => ['fio',    'position',    'desc_ru'],
                                 'en' => ['fio_en', 'position_en', 'desc_en'],
                             ] as $locale => [$fioField, $posField, $descField]) {

                        $fio = trim((string)($user->$fioField ?? ''));
                        if (!$fio) continue;

                        [$name, $surname] = parseFio($fio);

                        $translations[] = [
                            'user_id' => $userId,
                            'locale'  => $locale,
                            'name'    => $name,
                            'surname' => $surname,
                            'position'=> trim((string)($user->$posField ?? '')) ?: null,
                            'bio'     => trim((string)($user->$descField ?? '')) ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                // один upsert на chunk
                DB::table('user_translations')->upsert(
                    $translations,
                    ['user_id', 'locale'],
                    ['name', 'surname', 'position', 'bio', 'updated_at']
                );
            });

        echo PHP_EOL;

        return [
            'stats' => true,
            'total_processed' => $processed,
            'imported' => $inserted,
            'skipped' => $processed - $inserted,
            'batches' => $batches,
            'errors' => $errors,
        ];
    }

    public function importArticles(string $tableName = self::PUBLICATIONS_TABLE, int $batchLimit = 100, ?int $totalLimit = null): array
    {
        $this->defaultAuthor = User::first();

        $this->showDebug && $this->info('[IMPORT] LIMITS ', [  //@todo
            'batchLimit' => $batchLimit,
            'limit'      => $totalLimit,
        ]);

        if (!$this->defaultAuthor) {
            throw new \Exception('No users found');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $totalProcessed = 0;
        $batchNumber = 0;
        $lastLogTime = microtime(true);

        $allowedTables = [
            self::PUBLICATIONS_TABLE,
            self::OPINIONSNEW_TABLE,
            self::OPINIONS_TABLE,
        ];

        //========================
        // PREPARE  MAPs
        //========================
        if (in_array($tableName, $allowedTables)) {

            /* fill category data author rel */
            $dictData = $this->fetchDictTablesFromDb();
            $dictData = $this->buildCategoryMappingsFromSlug(
                $dictData['dict_category'] ?? [],
                $dictData['dict_sub_category'] ?? []
            );
            $this->categoryIdMap = $dictData['categories'];
            $this->subCategoryIdMap = $dictData['subcategories'];

            /* fill user data author rel */
            $this->usersMap = User::pluck('id', 'old_id')->toArray();
        }

        //==  CHOOSE  TABLE FOR IMPORT
        match ($tableName) {
            self::PUBLICATIONS_TABLE =>
            $this->processTable(
                self::PUBLICATIONS_TABLE,
                fn($lastId, $limit) => $this->fetchPublicationsFromDb($lastId, $limit, 'publications'),
                null,
                $batchLimit,
                $totalLimit,
                $imported,
                $skipped,
                $totalProcessed,
                $batchNumber,
                $errors,
                $lastLogTime
            ),
            self::OPINIONS_TABLE =>
            $this->processTable(
                self::OPINIONS_TABLE,
                fn($lastId, $limit) => $this->fetchOpinionsFromDb($lastId, $limit),
                fn($item) => $this->convertToPublicationFormat($item),
                $batchLimit,
                $totalLimit,
                $imported,
                $skipped,
                $totalProcessed,
                $batchNumber,
                $errors,
                $lastLogTime
            ),
            self::OPINIONSNEW_TABLE =>
            $this->processTable(
                self::OPINIONSNEW_TABLE,
                fn($lastId, $limit) => $this->fetchPublicationsFromDb($lastId, $limit, self::OPINIONSNEW_TABLE),
                null,
                $batchLimit,
                $totalLimit,
                $imported,
                $skipped,
                $totalProcessed,
                $batchNumber,
                $errors,
                $lastLogTime
            ),
            'persons' =>
            $this->processTable(
                'persons',
                fn($lastId, $limit) => $this->fetchPersonsFromDb($lastId, $limit),
                fn($item) => $this->convertToPublicationFormat($item),
                $batchLimit,
                $totalLimit,
                $imported,
                $skipped,
                $totalProcessed,
                $batchNumber,
                $errors,
                $lastLogTime
            ),
            'company' =>
            $this->processTable(
                'company',
                fn($lastId, $limit) => $this->fetchCompaniesFromDb($lastId, $limit),
                fn($item) => $this->convertToPublicationFormat($item),
                $batchLimit,
                $totalLimit,
                $imported,
                $skipped,
                $totalProcessed,
                $batchNumber,
                $errors,
                $lastLogTime
            ),
            default => throw new \InvalidArgumentException("Unknown table: {$tableName}"),
        };

        return [
            'stats'    => true,
            'total_processed' => $totalProcessed,
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'batches'  => $batchNumber,
        ];
    }


    //========== for help import ==================

    public function importTags(): array
    {
        $oldDb = DB::connection(self::OLD_COMMENTS);

        $inserted = 0;
        $translated = 0;

        $oldDb->table('dict_tag')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$inserted, &$translated) {

                $tags = [];
                $translations = [];

                foreach ($rows as $tag) {

//                    $name = $tag->name_ua   //@todo remove
//                        ?? $tag->name_ru
//                        ?? $tag->name_en
//                        ?? null;
//
//                    if (!$name) {
//                        $name = 'tag-' . $tag->id;
//                    }

                    $tags[] = [
                        'id' => $tag->id,
                        'view_count' => $tag->sumcntview ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $inserted++;

                    foreach (['uk' => 'ua', 'ru' => 'ru', 'en' => 'en'] as $locale => $oldLocale) {

                        $tName = trim((string)($tag->{"name_{$oldLocale}"} ?? ''));
                        if ($tName === '') continue;

                        $slug = trim((string)($tag->{"translit_{$oldLocale}"} ?? ''));

                        if ($slug === '' || $slug === '?') {
                            $slug = Str::slug($tName);
                        }

                        if ($slug === '') continue;

                        $translations[] = [
                            'tag_id' => $tag->id,
                            'locale' => $locale,
                            'title'  => $tName,
                            'slug'   => $slug,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $translated++;
                    }
                }

                DB::table('tags')->upsert(
                    $tags,
                    ['id'],
                    ['view_count', 'updated_at']
                );

                DB::table('tag_translations')->upsert(
                    $translations,
                    ['tag_id', 'locale'],
                    ['title', 'slug', 'updated_at']
                );
            });

        return [
            'tags' => $inserted,
            'translations' => $translated,
        ];
    }

    public function importTagRelations($tableName, $truncate = false)
    {
        $sourceTable = match ($tableName) {
            self::PUBLICATIONS_TABLE => 'mtm_publication_tag',
            self::OPINIONSNEW_TABLE  => 'mtm_opinions_tag',
            default => throw new \InvalidArgumentException("Unknown table"),
        };

        $chunkSize = 1000;
        $oldDb = DB::connection(self::OLD_COMMENTS);

        $this->showDebug && $this->info('[ARTICLE_TAGS] Starting import...');

        if ($truncate) {
            DB::table('article_tags')->delete();
        }

        $totalInserted = 0;

        $oldDb->table($sourceTable)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$totalInserted) {

                DB::beginTransaction();

                try {
                    $publicationIds = $rows->pluck('publication_id')->unique()->values();

                    $articlesMap = DB::table('articles')
                        ->whereIn('old_id', $publicationIds)
                        ->pluck('id', 'old_id');

                    $batch = [];

                    foreach ($rows as $row) {

                        $articleId = $articlesMap[$row->publication_id] ?? null;

                        if (!$articleId) {
                            continue;
                        }

                        $batch[] = [
                            'article_id' => $articleId,
                            'tag_id'     => $row->tag_id,
                            'created_at' => now()->toDateTimeString(),
                            'updated_at' => now()->toDateTimeString(),
                        ];
                    }

                    if (!empty($batch)) {

                        // убираем дубли внутри chunk
                        $batch = collect($batch)
                            ->unique(fn($i) => $i['article_id'].'-'.$i['tag_id'])
                            ->values()
                            ->all();

                        // лучше чем upsert для pivot
                        DB::table('article_tags')->insertOrIgnore($batch);

                        $totalInserted += count($batch);
                    }

                    DB::commit();

                    $this->showDebug && $this->info('[ARTICLE_TAGS] processed chunk', [
                        'rows' => count($rows),
                        'unique_batch' => count($batch),
                        'total' => $totalInserted,
                        'last_id' => $rows->last()->id,
                    ]);

                } catch (\Throwable $e) {

                    DB::rollBack();

                    $this->showDebug && $this->info('[ARTICLE_TAGS] chunk failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        $this->showDebug && $this->info('[ARTICLE_TAGS] DONE', [
            'total_inserted' => $totalInserted,
        ]);
    }

    private function getArticleLastProcessedOldId(string $tableName): ?int
    {
        $typeIds = $this->sourceArticleTypeMap[$tableName] ?? null;

        if (!$typeIds) {
            throw new \InvalidArgumentException("Unknown table: {$tableName}");
        }

        return Article::whereIn('type_id', $typeIds)
            ->whereNotNull('old_id')
            ->max('old_id');
    }

    private function processTable(
        string $tableName,
        callable $fetchCallback,
        callable $transformCallback = null,
        int $batchLimit,
        ?int $totalLimit,
        int &$imported,
        int &$skipped,
        int &$totalProcessed,
        int &$batchNumber,
        array &$errors,
        float &$lastLogTime
    ): void {
        $this->showReport && $this->info("[IMPORT] ===== Start: {$tableName} =====");

        $lastProcessedOldId = $this->getArticleLastProcessedOldId($tableName);

        while (true) {
            if ($totalLimit !== null && $totalProcessed >= $totalLimit) {
                break;
            }

            $bulkBuffer = [];
            $batchNumber++;
            $batchStart = microtime(true);

            $items = $fetchCallback($lastProcessedOldId, $batchLimit);

            if (empty($items)) {
                break;
            }

            $totalInBatch = count($items);   //
            $processedInBatch = 0;           //

            foreach ($items as $item) {
                if ($totalLimit !== null && $totalProcessed >= $totalLimit) {
                    break 2;
                }

                try {
                    $id = $item['id'] ?? $item[0] ?? null;

                    if ($transformCallback) {
                        $item = $transformCallback($item);
                    }

                    $bulkBuffer[] = $item;

                    $totalProcessed++;

                } catch (\Exception $e) {

                    $skipped++;
                    $errors[] = [
                        'table' => $tableName,
                        'error' => $e->getMessage(),
                    ];

                    $totalProcessed++;
                }

                $processedInBatch++; //
                echo "\r[{$tableName}] batch {$batchNumber} ({$processedInBatch}/{$totalInBatch})";//
            }

            $lastProcessedOldId = max(array_column($items, 'id'));

            if (!empty($bulkBuffer)) {

                $res = $this->importArticlesBulk($bulkBuffer, $tableName);

                $imported = $imported + $res['imported'];
                $skipped  = $skipped  + $res['skipped'];
            }

            $this->showDebug && $this->info('[IMPORT] Batch done', [
                'table' => $tableName,
                'batch' => $batchNumber,
                'processed' => $totalProcessed,
                'imported' => $imported,
                'skipped' => $skipped,
                'time_sec' => round(microtime(true) - $batchStart, 2),
            ]);

            // прогресс раз в 15 сек
            $now = microtime(true);
            if (($now - $lastLogTime) > 15) {
                $this->showProgress && $this->info('[IMPORT] Progress', [
                    'table'     => $tableName,
                    'processed' => $totalProcessed,
                    'imported'  => $imported,
                    'skipped'   => $skipped,
                ]);
                $lastLogTime = $now;
            }
        }

        if (in_array($tableName, [self::OPINIONSNEW_TABLE, self::PUBLICATIONS_TABLE])) {
            $this->importTagRelations($tableName);
        }

        $this->showReport && $this->info("[IMPORT] ===== Done: {$tableName} =====", [
            'processed' => $totalProcessed,
            'imported'  => $imported,
            'skipped'   => $skipped,
        ]);
    }


    private function importArticlesBulk(array $items, string $sourceTable): array
    {
        $articles = [];
        $translations = [];
        $authors = [];
        $editors = [];
        $markers = [];
        $seoMeta = [];
        $tags    = [];
        $articleSites = [];
        $seoMetaTranslations = [];
        $seoMetaTranslationsTmp = [];
        $typeIds = $this->getTypeIdsByTableName($sourceTable);
        $oldIds = [];

        foreach ($items as $item) {

            $publicationId = (int)$item['id'];
            $oldIds[] = $publicationId;

            $date      = $this->parseDate($item['date'] ?? null);
            $createdAt = $this->parseDate($item['add_date'] ?? null) ?? $date;
            $updatedAt = $this->parseDate($item['last_edit'] ?? null);

            // -------------------------
            // CATEGORY (refactor ready)
            // -------------------------
            $categoryId = $this->resolveCategoryId($item);
            $typeId     = $this->resolveTypeId($item);

            $articles[] = [
                'old_id'       => $publicationId,
                'category_id'  => $categoryId,
                'type_id'      => $typeId,
                'status'       => Article::STATUS_PUBLISHED,
                'published_at' => $date,
                'source_url'   => $item['source'] ?? null,
                'views'        => (int)($item['views'] ?? 0),
                'created_at'   => $createdAt,
                'updated_at'   => $updatedAt,
            ];
        }

        $articles = collect($articles)
            ->unique(fn ($a) => $a['type_id'].'-'.$a['old_id'])
            ->values()
            ->all();

        // INSERT ARTICLES
        $imported = count($articles);

        DB::table('articles')->insert($articles);

        // MAP old_id → id
        $articleMap = DB::table('articles')
            ->whereIn('type_id', $typeIds)
            ->whereIn('old_id', $oldIds)
            ->pluck('id', 'old_id')
            ->toArray();

        foreach ($items as $item) {

            $articleId = $articleMap[$item['id']] ?? null;
            if (!$articleId) continue;

            // ----------------------------------
            // NORMALIZE ITEM (NEW LAYER)
            // ----------------------------------
            $data = $this->extractPublicationData($item);
            $locales = $this->buildLocales($data);

            // -------------------------
            // TRANSLATIONS (clean)
            // -------------------------
            foreach ($locales as $locale => $t) {

                if (empty($t['title'])) {
                    continue;
                }

                $translations[] = [
                    'article_id'    => $articleId,
                    'locale'        => $locale,
                    'title'         => $t['title'],
                    'title_with_markers' => $t['title'],
                    'content_html'  => $t['content_html'],
                    'excerpt'       => $t['excerpt'],
                    'slug'          => $t['slug'],
                ];
            }

            // -------------------------
            // AUTHORS / EDITORS
            // -------------------------
            [$authorId, $editorId] = $this->resolveAuthors($item, $sourceTable);

            $authors[] = [
                'article_id' => $articleId,
                'user_id'    => $authorId,
            ];

            $editors[] = [
                'article_id' => $articleId,
                'user_id'    => $editorId,
            ];

            // -------------------------
            // MARKERS
            // -------------------------
            foreach ($this->resolveMarkers($item) as $markerId) {
                $markers[] = [
                    'article_id' => $articleId,
                    'marker_id'  => $markerId,
                ];
            }

            // -------------------------
            // SEO
            // -------------------------
            if ($this->hasSeo($item)) {

                $seoMeta[] = [
                    'entity_type' => Article::class,
                    'entity_id'   => $articleId,
                ];

                $seoMetaTranslationsTmp[$articleId] = $item;
            }

            // -------------------------
            // SITES
            // -------------------------
            $articleSites[] = [
                'article_id' => $articleId,
                'site_id'    => $this->defaultSiteId,
            ];
        }

        // =========================
        // BULK INSERTS
        // =========================

        if ($translations) {
            DB::table('article_translations')->insert($translations);
        }

        if ($authors) {
            DB::table('article_authors')->insert($authors);
        }

        if ($editors) {
            DB::table('article_editors')->upsert(
                $editors,
                ['article_id', 'user_id'],
                []
            );
        }

        if ($markers) {
            DB::table('article_marker')->insert($markers);
        }

        if ($seoMeta) {
            DB::table('seo_meta')->insert($seoMeta);

            $seoMetaMap = DB::table('seo_meta')
                ->whereIn('entity_id', array_column($seoMeta, 'entity_id'))
                ->pluck('id', 'entity_id');
        }

        if ($tags) {
            DB::table('article_tags')->upsert(
                $tags,
                ['article_id', 'tag_id'],
                []
            );
        }

        foreach ($seoMetaTranslationsTmp as $articleId => $item) {

            $seoMetaId = $seoMetaMap[$articleId] ?? null;
            if (!$seoMetaId) continue;

            foreach (['ua','ru','en'] as $l) {

                $title = $item["meta_{$l}"] ?? null;
                $desc  = $item["meta_desc_{$l}"] ?? null;

                if (!$title && !$desc) continue;

                $seoMetaTranslations[] = [
                    'seo_meta_id'      => $seoMetaId,
                    'locale'           => $l === 'ua' ? 'uk' : $l,
                    'meta_title'       => $title,
                    'meta_description' => $desc,
                ];
            }
        }

        if ($seoMetaTranslations) {
            DB::table('seo_meta_translations')->insert($seoMetaTranslations);
        }

        if ($articleSites) {
            DB::table('article_sites')->upsert(
                $articleSites,
                ['article_id', 'site_id'],
                []
            );
        }

        return [
            'imported' => $imported,
            'skipped'  => count($items) - $imported,
        ];
    }

    private function resolveCategoryId(array $item): ?int
    {
        $categoryId = isset($item['category_id']) ? (int)$item['category_id'] : null;

        $subCategoryOldId = isset($item['sub_id']) && $item['sub_id'] !== null
            ? (int)$item['sub_id']
            : null;

        // 1. приоритет — подкатегория
        if ($subCategoryOldId && isset($this->subCategoryIdMap[$subCategoryOldId])) {
            return $this->subCategoryIdMap[$subCategoryOldId];
        }

        // 2. обычная категория
        if ($categoryId && isset($this->categoryIdMap[$categoryId])) {
            return $this->categoryIdMap[$categoryId];
        }

        // 3. fallback отключаем в bulk (ВАЖНО)
        return null;
    }

    private function resolveTypeId(array $item): ?int
    {
        $subType = (int)($item['sub_type'] ?? 0);

        return isset(ArticleType::TYPES[$subType]) ? $subType : null;
    }

    private function resolveAuthors(array $item, string $sourceTable): array
    {
        $default = $this->defaultAuthor->id;

        $authorOld = (int)($item['author_id'] ?? 0);
        $editorOld = (int)($item['last_editor'] ?? 0);

        if ($sourceTable === self::OPINIONSNEW_TABLE) {

            $mapped = $this->usersMap[$authorOld] ?? $default;

            return [$mapped, $mapped];
        }

        if (in_array($sourceTable, [self::COMPANY_TABLE, self::PERSONS_TABLE])) {
            return [$default, $default];
        }

        $author = $this->usersMap[$authorOld] ?? $default;
        $editor = $this->usersMap[$editorOld] ?? $author;

        return [$author, $editor];
    }

    private function resolveMarkers(array $publication): array
    {
        $markers = [];

        $newsMarker = isset($publication['news_marker']) ? (int)$publication['news_marker'] : null;
        $exclusive  = isset($publication['exclusive']) ? (int)$publication['exclusive'] : 0;
        $inside     = isset($publication['inside']) ? (int)$publication['inside'] : 0;
        $fastNews   = isset($publication['fast_news']) ? (int)$publication['fast_news'] : 0;

        // dynamic marker
        if ($newsMarker && isset(self::MARKERS[$newsMarker])) {
            $markers[] = $newsMarker;
        }

        // flags
        if ($exclusive === 1 && isset(self::MARKERS[6])) {
            $markers[] = 6;
        }

        if ($inside === 1 && isset(self::MARKERS[7])) {
            $markers[] = 7;
        }

        if ($fastNews === 1 && isset(self::MARKERS[8])) {
            $markers[] = 8;
        }

        return array_values(array_unique($markers));
    }

    private function extractPublicationData(array $publication): array
    {
        return [
            'title' => [
                'ua' => !empty($publication['title_ua']) ? trim(strip_tags($publication['title_ua'])) : null,
                'ru' => !empty($publication['title_ru']) ? trim(strip_tags($publication['title_ru'])) : null,
                'en' => !empty($publication['title_en']) ? trim(strip_tags($publication['title_en'])) : null,
            ],

            'content' => [
                'ua' => $publication['content_ua'] ?? null,
                'ru' => $publication['content_ru'] ?? null,
                'en' => $publication['content_en'] ?? null,
            ],

            'excerpt' => [
                'ua' => !empty($publication['anons_ua']) ? trim(strip_tags($publication['anons_ua'])) : null,
                'ru' => !empty($publication['anons_ru']) ? trim(strip_tags($publication['anons_ru'])) : null,
                'en' => !empty($publication['anons_en']) ? trim(strip_tags($publication['anons_en'])) : null,
            ],

            'slug' => [
                'ua' => $publication['translit_ua'] ?? null,
                'ru' => $publication['translit_ru'] ?? null,
                'en' => $publication['translit_en'] ?? null,
            ],

            'seo' => [
                'meta_title' => [
                    'ua' => $publication['meta_ua'] ?? null,
                    'ru' => $publication['meta_ru'] ?? null,
                    'en' => $publication['meta_en'] ?? null,
                ],
                'meta_description' => [
                    'ua' => $publication['meta_desc_ua'] ?? null,
                    'ru' => $publication['meta_desc_ru'] ?? null,
                    'en' => $publication['meta_desc_en'] ?? null,
                ],
            ],

            'source_url' => !empty($publication['source']) ? trim($publication['source']) : null,
            'views' => isset($publication['views']) ? (int)$publication['views'] : 0,
        ];
    }

    private function hasSeo(array $item): bool
    {
        foreach (['ua','ru','en'] as $l) {
            if (!empty($item["meta_{$l}"]) || !empty($item["meta_desc_{$l}"])) {
                return true;
            }
        }

        return false;
    }

    private function buildLocales(array $data): array
    {
        return [
            'uk' => [
                'title'        => $data['title']['ua'],
                'content_html' => $data['content']['ua'],
                'excerpt'      => $data['excerpt']['ua'],
                'slug'         => $data['slug']['ua'],
                'meta_title'   => $data['seo']['meta_title']['ua'],
                'meta_description' => $data['seo']['meta_description']['ua'],
            ],
            'ru' => [
                'title'        => $data['title']['ru'],
                'content_html' => $data['content']['ru'],
                'excerpt'      => $data['excerpt']['ru'],
                'slug'         => $data['slug']['ru'],
                'meta_title'   => $data['seo']['meta_title']['ru'],
                'meta_description' => $data['seo']['meta_description']['ru'],
            ],
            'en' => [
                'title'        => $data['title']['en'],
                'content_html' => $data['content']['en'],
                'excerpt'      => $data['excerpt']['en'],
                'slug'         => $data['slug']['en'],
                'meta_title'   => $data['seo']['meta_title']['en'],
                'meta_description' => $data['seo']['meta_description']['en'],
            ],
        ];
    }


    //======================================================  HELPERS  ==================================

    /**
     * Parse date from various formats
     *
     * @param string|null $dateString
     * @return \Carbon\Carbon|null
     */
    private function parseDate(?string $dateString): ?\Carbon\Carbon
    {
        if (empty($dateString) || strtoupper($dateString) === 'NULL') {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($dateString);

            $minDate = \Carbon\Carbon::create(1970, 1, 2, 0, 0, 0);
            if ($date->lt($minDate)) {
                return $minDate;
            }

            $maxDate = \Carbon\Carbon::now();
            if ($date->gt($maxDate)) {
                return $maxDate;
            }

            return $date;
        } catch (\Exception $e) {
            // Log::warning('Failed to parse date', ['date' => $dateString]);
            return null;
        }
    }

    private function getEmail(object $user, string $suffix): string
    {
        $email = '';

        if (property_exists($user, 'email')) {
            $email = trim((string)$user->email);
        }

        if (!$email) {
            $email = $suffix . $user->id . '@mail.local';
        }

        return $email;
    }

    private function ts(?int $timestamp): ?string
    {
        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }
}