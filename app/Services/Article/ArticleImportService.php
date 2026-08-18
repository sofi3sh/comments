<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\Category;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\Articles\Translate\CategoryTranslation;
use App\Models\Seo\SeoMeta;
use App\Models\Seo\Translate\SeoMetaTranslation;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ArticleImportService
{
    public const OLD_COMMENTS       = 'oldcommentar';
    public const PUBLICATIONS_TABLE = 'publications';
    public const OPINIONSNEW_TABLE  = 'opinionsnew';
    public const OPINIONS_TABLE     = 'opinions';
    protected bool $showDebug = false;
    protected bool $showProgress = true;
    protected bool $showReport = true;
    protected bool $showError = true;
    protected array $usersMap = [];
    protected array $sourceArticleTypeMap = [
        'publications' => [1, 2, 3],
        'opinionsnew'  => [6],
        'persons'      => [4],
        'company'      => [5],
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

    /**
     * Fetch publications from old database using SQL query
     *
     * @param int|null $lastId Last processed publication ID (for pagination)
     * @param int $limit Number of publications to fetch per batch
     * @param string $tableName Table name to fetch from (default: 'publications')
     * @return array
     */
    public function fetchPublicationsFromDb(?int $lastId = null, int $limit = 100, string $tableName = 'publications'): array
    {
        $this->showDebug && $this->info('[IMPORT] fetchPublicationsFromDb called', [
            'last_id' => $lastId,
            'limit' => $limit,
            'table_name' => $tableName,
        ]);

        $startTime = microtime(true);

        try {
            // Use old database connection
            $oldDb = DB::connection(self::OLD_COMMENTS);

            // Validate table name to prevent SQL injection
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
                $countSql = "SELECT COUNT(*) as total FROM `{$tableName}` p";
                $totalBeforeFilter = $oldDb->selectOne($countSql);
                $totalCountBeforeFilter = $totalBeforeFilter->total ?? 0;

                // Get count with tosite filter
                $countSqlFiltered = "SELECT COUNT(*) as total 
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
            $oldDb = DB::connection('oldcommentar');
            
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
     * Clear cached dict tables
     *
     * @return void
     */
    public function clearDictCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'dict_category');
        Cache::forget(self::CACHE_PREFIX . 'dict_sub_category');
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
                $slugFromDict = $row['dir'] ?? null;
            } else {
                if (count($row) < 2) {
                    continue;
                }
                $oldId = $row[0];
                $nameUa = $row[1] ?? '';
                $nameRu = $row[2] ?? '';
                $nameEn = $row[3] ?? '';
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
                    null,
                    $slug,
                    false
                );
                $created++;
            } else {
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
                    $parentNewId,
                    $slug,
                    false
                );
                $subCreated++;
            } else {
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
     * @param string $nameUa
     * @param string $nameRu
     * @param string $nameEn
     * @param int|null $parentId
     * @param string|null
     * @param bool
     * @return Category
     */
    private function findOrCreateCategory(string $nameUa, string $nameRu, string $nameEn, ?int $parentId, ?string $slugOverride = null, bool $generateSlugIfMissing = true): Category
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
            if ($nameUa && !$category->translations()->where('locale', 'uk')->exists()) {
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => 'uk',
                    'name' => $nameUa,
                ]);
            }
            if ($nameRu && !$category->translations()->where('locale', 'ru')->exists()) {
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => 'ru',
                    'name' => $nameRu,
                ]);
            }
            if ($nameEn && !$category->translations()->where('locale', 'en')->exists()) {
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => 'en',
                    'name' => $nameEn,
                ]);
            }
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
                if ($nameUa && !$category->translations()->where('locale', 'uk')->exists()) {
                    CategoryTranslation::create([
                        'category_id' => $category->id,
                        'locale' => 'uk',
                        'name' => $nameUa,
                    ]);
                }
                if ($nameRu && !$category->translations()->where('locale', 'ru')->exists()) {
                    CategoryTranslation::create([
                        'category_id' => $category->id,
                        'locale' => 'ru',
                        'name' => $nameRu,
                    ]);
                }
                if ($nameEn && !$category->translations()->where('locale', 'en')->exists()) {
                    CategoryTranslation::create([
                        'category_id' => $category->id,
                        'locale' => 'en',
                        'name' => $nameEn,
                    ]);
                }
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
        
        // Create translations for category
        if ($nameUa) {
            CategoryTranslation::create([
                'category_id' => $category->id,
                'locale' => 'uk',
                'name' => $nameUa,
            ]);
        }
        if ($nameRu) {
            CategoryTranslation::create([
                'category_id' => $category->id,
                'locale' => 'ru',
                'name' => $nameRu,
            ]);
        }
        if ($nameEn) {
            CategoryTranslation::create([
                'category_id' => $category->id,
                'locale' => 'en',
                'name' => $nameEn,
            ]);
        }
        
        return $category;
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

        if (!$this->defaultAuthor) {
            throw new \Exception('No users found');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $totalProcessed = 0;
        $batchNumber = 0;
        $lastLogTime = microtime(true);

        $this->usersMap = User::withTrashed()->pluck('id', 'old_id')->toArray();

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
            'stats' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => $totalProcessed,
            'batches' => $batchNumber,
        ];
    }

    //========== for help import ==================
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

        $lastProcessedId = $this->getArticleLastProcessedId($tableName);

        while (true) {
            if ($totalLimit !== null && $totalProcessed >= $totalLimit) {
                break;
            }

            $batchNumber++;
            $batchStart = microtime(true);

            $items = $fetchCallback($lastProcessedId, $batchLimit);

            $totalInBatch = count($items);   //
            $processedInBatch = 0;           //

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                if ($totalLimit !== null && $totalProcessed >= $totalLimit) {
                    break 2;
                }

                try {
                    $id = $item['id'] ?? $item[0] ?? null;

                    if ($transformCallback) {
                        $item = $transformCallback($item);
                    }

                    $result = $this->importSingleArticle($item);

                    if ($result === null) {
                        $skipped++;
                    } else {
                        $imported++;
                    }

                    $totalProcessed++;

                } catch (\Exception $e) {
                    $skipped++;

                    $errors[] = [
                        'table' => $tableName,
                        'error' => $e->getMessage(),
                    ];

                    if (!empty($id)) {
                        $lastProcessedId = (int) $id;
                    }

                    $totalProcessed++;
                }

//                $processedInBatch++;
//                echo "\r[{$tableName}] batch {$batchNumber} ({$processedInBatch}/{$totalInBatch})";
                echo "\r[{$tableName}] batch {$batchNumber}";
            }

            $lastProcessedId = max(array_column($items, 'id'));

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
                    'table' => $tableName,
                    'processed' => $totalProcessed,
                    'imported' => $imported,
                    'skipped' => $skipped,
                ]);
                $lastLogTime = $now;
            }
        }

        if (in_array($tableName, [self::OPINIONSNEW_TABLE, self::PUBLICATIONS_TABLE])) {
            $this->importTagRelations($tableName);
        }

        $this->showReport && $this->info("[IMPORT] ===== Done: {$tableName} =====", [
            'processed' => $totalProcessed,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }

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

                    $name = $tag->name_ua
                        ?? $tag->name_ru
                        ?? $tag->name_en
                        ?? null;

                    if (!$name) {
                        $name = 'tag-' . $tag->id;
                    }

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
            ->orderBy('id') // важно
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
                        ];
                    }

                    if (!empty($batch)) {

                        // убираем дубли внутри chunk
                        $batch = collect($batch)
                            ->unique(fn($i) => $i['article_id'].'-'.$i['tag_id'])
                            ->values()
                            ->all();

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

    private function getArticleLastProcessedId(string $tableName): ?int
    {
        $typeIds = $this->sourceArticleTypeMap[$tableName] ?? null;

        if (!$typeIds) {
            throw new \InvalidArgumentException("Unknown table: {$tableName}");
        }

        return Article::whereIn('type_id', $typeIds)
            ->whereNotNull('old_id')
            ->max('old_id');
    }

    /**
     * Import single article
     *
     * @param array $publication
     * @return Article|null
     * @throws \Exception
     */
    public function importSingleArticle(array $publication): ?Article
    {
        DB::beginTransaction();
        
        try {
            $publicationId = isset($publication['id']) ? (int)$publication['id'] : null;
            $sourceTable = $publication['_source_table'] ?? 'publications';
            // Extract data from publication array
            // Since we're using explicit field selection in SQL, data should always be associative
            $date = $this->parseDate($publication['date'] ?? null);
            // add_date: if empty, fallback to publication date
            $addDate = $this->parseDate($publication['add_date'] ?? null);
            $createdAt = $addDate ?? $date;
            // last_edit: used for updated_at
            $updatedAt = $this->parseDate($publication['last_edit'] ?? null);
            // Extract titles for different locales (strip any HTML tags just in case)
            $titleUa = !empty($publication['title_ua']) ? trim(strip_tags($publication['title_ua'])) : null;
            $titleRu = !empty($publication['title_ru']) ? trim(strip_tags($publication['title_ru'])) : null;
            $titleEn = !empty($publication['title_en']) ? trim(strip_tags($publication['title_en'])) : null;
            // Extract content for different locales (will be saved to content_html)
            $contentUa = !empty($publication['content_ua']) ? $publication['content_ua'] : null;
            $contentRu = !empty($publication['content_ru']) ? $publication['content_ru'] : null;
            $contentEn = !empty($publication['content_en']) ? $publication['content_en'] : null;
            // Extract anons (excerpts) for different locales (will be saved to article_translations.excerpt)
            $anonsUa = !empty($publication['anons_ua']) ? trim(strip_tags($publication['anons_ua'])) : null;
            $anonsRu = !empty($publication['anons_ru']) ? trim(strip_tags($publication['anons_ru'])) : null;
            $anonsEn = !empty($publication['anons_en']) ? trim(strip_tags($publication['anons_en'])) : null;
            // Extract source URL (source -> articles.source_url)
            $sourceUrl = !empty($publication['source']) ? trim($publication['source']) : null;
            // Extract views (views -> articles.views)
            $views = isset($publication['views']) && $publication['views'] !== null ? (int)$publication['views'] : 0;
            // Extract meta titles for SEO (meta_ua, meta_ru, meta_en -> seo_meta_translations.meta_title)
            $metaUa = !empty($publication['meta_ua']) ? trim($publication['meta_ua']) : null;
            $metaRu = !empty($publication['meta_ru']) ? trim($publication['meta_ru']) : null;
            $metaEn = !empty($publication['meta_en']) ? trim($publication['meta_en']) : null;
            // Extract meta descriptions for SEO (meta_desc_ua, meta_desc_ru, meta_desc_en -> seo_meta_translations.meta_description)
            $metaDescUa = !empty($publication['meta_desc_ua']) ? trim($publication['meta_desc_ua']) : null;
            $metaDescRu = !empty($publication['meta_desc_ru']) ? trim($publication['meta_desc_ru']) : null;
            $metaDescEn = !empty($publication['meta_desc_en']) ? trim($publication['meta_desc_en']) : null;
            // Extract slugs (translit_ua, translit_ru, translit_en -> article_translations.slug)
            $slugUa = !empty($publication['translit_ua']) ? trim($publication['translit_ua']) : null;
            $slugRu = !empty($publication['translit_ru']) ? trim($publication['translit_ru']) : null;
            $slugEn = !empty($publication['translit_en']) ? trim($publication['translit_en']) : null;
            // Extract category data from dict_category (via JOIN in SQL query)
            $categoryId = isset($publication['category_id']) ? (int)$publication['category_id'] : null;
            $categoryNameUa = !empty($publication['category_name_ua']) ? trim($publication['category_name_ua']) : null;
            $categoryNameRu = !empty($publication['category_name_ru']) ? trim($publication['category_name_ru']) : null;
            $categoryNameEn = !empty($publication['category_name_en']) ? trim($publication['category_name_en']) : null;
            // Extract subcategory ID from publications/opinionsnew (sub_id -> dict_sub_category.id)
            $subCategoryOldId = isset($publication['sub_id']) && $publication['sub_id'] !== null
                ? (int)$publication['sub_id']
                : null;

            $subType = isset($publication['sub_type']) ? (int)$publication['sub_type'] : null;
            // Extract news_marker (news_marker -> markers.id for article_marker relationship)
            $newsMarker = isset($publication['news_marker']) && $publication['news_marker'] !== null ? (int)$publication['news_marker'] : null;
            // Extract marker flags (exclusive, inside, fast_news -> markers.id for article_marker relationship)
            $exclusive = isset($publication['exclusive']) && $publication['exclusive'] !== null ? (int)$publication['exclusive'] : 0;
            $inside    = isset($publication['inside']) && $publication['inside'] !== null ? (int)$publication['inside'] : 0;
            $fastNews  = isset($publication['fast_news']) && $publication['fast_news'] !== null ? (int)$publication['fast_news'] : 0;
            // Extract author
            $lastEditorId = isset($publication['last_editor']) ? (int)$publication['last_editor'] : null;
            $authorId     = isset($publication['author_id']) ? (int)$publication['author_id'] : null;
            $lastEditorId = $lastEditorId ?: $authorId;

            $finalCategoryId = null;
            
            if ($subCategoryOldId && isset($this->subCategoryIdMap[$subCategoryOldId])) {
                $finalCategoryId = $this->subCategoryIdMap[$subCategoryOldId];
                $this->showDebug && $this->info('[IMPORT] [ARTICLES] Підкатегорія визначена з мапи', [
                    'old_sub_category_id' => $subCategoryOldId,
                    'new_category_id' => $finalCategoryId,
                    'old_category_id' => $categoryId,
                ]);
            }
            elseif ($categoryId && isset($this->categoryIdMap[$categoryId])) {
                $finalCategoryId = $this->categoryIdMap[$categoryId];
                $this->showDebug && $this->info('[IMPORT] [ARTICLES] Категорія визначена з мапи', [
                    'old_category_id' => $categoryId,
                    'new_category_id' => $finalCategoryId,
                ]);
            }
            elseif ($categoryId && ($categoryNameUa || $categoryNameRu || $categoryNameEn)) {
                // Знаходимо або створюємо категорію за назвами з dict_category
                $category = $this->findOrCreateCategory(
                    $categoryNameUa ?? '',
                    $categoryNameRu ?? '',
                    $categoryNameEn ?? '',
                    null
                );
                $finalCategoryId = $category->id;
                
                $this->showDebug && $this->info('[IMPORT] [ARTICLES] Категорія визначена через findOrCreateCategory', [
                    'category_id' => $finalCategoryId,
                    'category_name_ua' => $categoryNameUa,
                    'category_name_ru' => $categoryNameRu,
                    'category_name_en' => $categoryNameEn,
                ]);
            }

            $articleTypeId = null;
            if ($subType && isset(ArticleType::TYPES[$subType])) {
                $articleTypeId = $subType;
            }

            // Create article
            $this->showDebug && $this->info('[IMPORT] [ARTICLES] Створення статті в БД', [
                'publication_id' => $publicationId,
                'old_id' => $publicationId,
                'category_id' => $finalCategoryId,
                'type_id' => $articleTypeId,
                'published_at' => $date ? $date->toDateTimeString() : null,
                'source_url' => $sourceUrl,
                'adder' => $authorId,
                'authorId'  => $authorId,
                'views' => $views,
                'exclusive'  => $exclusive,
                'inside' => $inside,
                'fastNews' => $fastNews,
                'newsMarker' => $newsMarker,
                'created_at' => $createdAt ? $createdAt->toDateTimeString() : null,
                'updated_at' => $updatedAt ? $updatedAt->toDateTimeString() : null,
            ]);

            $article = Article::create([
                'old_id'       => $publicationId, // Save old publication ID
                'category_id'  => $finalCategoryId,
                'type_id'      => $articleTypeId, // Foreign key to article_types table
                'status'       => Article::STATUS_PUBLISHED,
                'published_at' => $date,
                'source_url'   => $sourceUrl, // source -> articles.source_url
                'views'        => $views, // views -> articles.views
                'created_at'   => $createdAt, // add_date (or date) -> created_at
                'updated_at'   => $updatedAt, // updated_at: if last_edit is null, set default current timestamp
            ]);
            
            $this->showDebug && $this->info('[IMPORT] [ARTICLES] ===== СТАТТЮ СТВОРЕНО =====', [
                'article_id' => $article->id,
                'publication_id' => $publicationId,
                'old_id' => $publicationId,
                'title' => $titleUa ?: $titleRu ?: $titleEn,
                'source_url' => $sourceUrl,
                'views' => $views,
            ]);

            // Attach author
            if ($sourceTable === self::PUBLICATIONS_TABLE) {
                $originalAuthorId = $authorId;
                $originalEditorId = $lastEditorId;
                $authorId = $this->usersMap[$originalAuthorId] ?? $this->defaultAuthor->id;
                $lastEditorId = $this->usersMap[$originalEditorId] ?? $this->defaultAuthor->id;
                $article->authors()->attach($authorId);
                $article->editors()->attach($lastEditorId);
            }

            if ($sourceTable === self::OPINIONSNEW_TABLE) {
                $author = User::withTrashed()->where('old_id', $authorId)->first();
                $authorId = $author ? $author->id : $this->defaultAuthor->id;
                $article->authors()->attach($authorId);
                $article->editors()->attach($authorId);
            }

            // Attach default site
            $article->sites()->attach(1); //@todo
            
            // Create translations
            // Map: title_ua/ru/en -> article_translations.title
            // Map: content_ua/ru/en -> article_translations.content_html
            // Map: anons_ua/ru/en -> article_translations.excerpt
            // Map: translit_ua/ru/en -> article_translations.slug
            $locales = [
                'uk' => [
                    'title' => $titleUa, 
                    'content_html' => $contentUa,
                    'excerpt' => $anonsUa,
                    'slug' => $slugUa,
                    'meta_title' => $metaUa, 
                    'meta_description' => $metaDescUa
                ],
                'ru' => [
                    'title' => $titleRu, 
                    'content_html' => $contentRu,
                    'excerpt' => $anonsRu,
                    'slug' => $slugRu,
                    'meta_title' => $metaRu, 
                    'meta_description' => $metaDescRu
                ],
                'en' => [
                    'title' => $titleEn, 
                    'content_html' => $contentEn,
                    'excerpt' => $anonsEn,
                    'slug' => $slugEn,
                    'meta_title' => $metaEn, 
                    'meta_description' => $metaDescEn
                ],
            ];
            
            $translationsCreated = 0;
            $translationsSkipped = 0;
            
            foreach ($locales as $locale => $data) {
                // Skip translation if no title (title is required)
                if (empty($data['title'])) {
                    $translationsSkipped++;
                    $this->showDebug && $this->info('[IMPORT] [ARTICLES] Пропущено переклад без заголовка', [
                        'locale' => $locale,
                        'article_id' => $article->id,
                        'publication_id' => $publicationId,
                    ]);
                    continue;
                }
                
                try {
                    ArticleTranslation::create([
                        'article_id' => $article->id,
                        'locale' => $locale,
                        'title' => $data['title'],
                        'title_with_markers' => $data['title'],  //todo
                        'content_html' => $data['content_html'], // content_ua/ru/en -> content_html
                        'excerpt' => $data['excerpt'], // anons_ua/ru/en -> excerpt
                        'slug' => $data['slug'], // translit_ua/ru/en -> slug
                    ]);
                    
                    $translationsCreated++;
                    $this->showDebug && $this->info('[IMPORT] [ARTICLES] Створено переклад статті', [
                        'locale' => $locale,
                        'article_id' => $article->id,
                        'publication_id' => $publicationId,
                        'title' => substr($data['title'], 0, 50) . '...',
                        'has_content' => !empty($data['content_html']),
                        'has_excerpt' => !empty($data['excerpt']),
                        'has_slug' => !empty($data['slug']),
                        'slug' => $data['slug'],
                    ]);
                } catch (\Exception $e) {
                    $this->showError && $this->info('[IMPORT] [ARTICLES] ПОМИЛКА створення перекладу', [
                        'locale' => $locale,
                        'article_id' => $article->id,
                        'publication_id' => $publicationId,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e; // Re-throw to be caught by outer try-catch
                }
            }
            
            $this->showDebug && $this->info('[IMPORT] [ARTICLES] Підсумок створення перекладів', [
                'article_id' => $article->id,
                'publication_id' => $publicationId,
                'translations_created' => $translationsCreated,
                'translations_skipped' => $translationsSkipped,
                'total_locales' => count($locales),
            ]);
            
            if ($translationsCreated === 0) {
                $this->showError && $this->info('[IMPORT] [ARTICLES] ===== УВАГА: СТАТТЯ СТВОРЕНА БЕЗ ЖОДНОГО ПЕРЕКЛАДУ =====', [
                    'article_id' => $article->id,
                    'publication_id' => $publicationId,
                ]);
            }
            
            // Create SEO meta if any meta data exists
            // Map: meta_ua/ru/en -> seo_meta_translations.meta_title
            // Map: meta_desc_ua/ru/en -> seo_meta_translations.meta_description
            $hasSeoData = false;
            foreach ($locales as $locale => $data) {
                if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                    $hasSeoData = true;
                    break;
                }
            }
            
            if ($hasSeoData) {
                $seoMeta = SeoMeta::create([
                    'entity_type' => Article::class,
                    'entity_id' => $article->id,
                ]);
                
                foreach ($locales as $locale => $data) {
                    // Skip if no meta data for this locale
                    if (empty($data['meta_title']) && empty($data['meta_description'])) {
                        continue;
                    }
                    
                    // Create SEO meta translation
                    // meta_ua/ru/en -> seo_meta_translations.meta_title
                    // meta_desc_ua/ru/en -> seo_meta_translations.meta_description
                    SeoMetaTranslation::create([
                        'seo_meta_id' => $seoMeta->id,
                        'locale' => $locale,
                        'meta_title' => $data['meta_title'], // meta_ua/ru/en -> meta_title
                        'meta_description' => $data['meta_description'], // meta_desc_ua/ru/en -> meta_description
                    ]);
                    
                    $this->showDebug && $this->info('[IMPORT] [ARTICLES] Створено SEO мета переклад', [
                        'locale' => $locale,
                        'article_id' => $article->id,
                        'has_meta_title' => !empty($data['meta_title']),
                        'has_meta_description' => !empty($data['meta_description']),
                    ]);
                }
            }

            /*        HANDLE MARKERS              */
            $markersToAttach = [];
            // dynamic marker
            if (!empty($newsMarker) && isset(self::MARKERS[$newsMarker])) {
                $markersToAttach[] = $newsMarker;
            }
            if ($exclusive == 1 && isset(self::MARKERS[6])) {
                $markersToAttach[] = 6;
            }
            if ($inside == 1 && isset(self::MARKERS[7])) {
                $markersToAttach[] = 7;
            }
            if ($fastNews == 1 && isset(self::MARKERS[8])) {
                $markersToAttach[] = 8;
            }

            $markersToAttach = array_values(array_unique($markersToAttach));
            $article->markers()->attach($markersToAttach);

            $this->showDebug && $this->info('[IMPORT] [ARTICLES] Всі операції завершено, виконуємо commit', [
                'article_id' => $article->id,
                'publication_id' => $publicationId,
            ]);
            
            DB::commit();
            
            $this->showDebug && $this->info('[IMPORT] [ARTICLES] ===== СТАТТЮ УСПІШНО ЗАВЕРШЕНО ІМПОРТ =====', [
                'article_id' => $article->id,
                'publication_id' => $publicationId,
                'old_id' => $publicationId,
            ]);
            
            return $article;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->showError && $this->info('[IMPORT] [ARTICLES] ===== ПРОКИНУТО ВИНЯТОК, ROLLBACK =====', [
                'publication_id' => $publicationId ?? null,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
            throw $e;
        }
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