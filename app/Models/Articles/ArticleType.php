<?php

namespace App\Models\Articles;

use App\Models\Settings\Locale;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ArticleType extends Model implements TranslatableContract
{
    use CrudTrait;
    use HasFactory;
    use Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['name'];

    protected string $translationModel = \App\Models\Articles\Translate\ArticleTypeTranslation::class;

    public const NEWS         = 'news';
    public const ARTICLE      = 'article';
    public const INTERVIEW    = 'interview';
    public const COMPANY      = 'company';
    public const PERSON       = 'person';
    public const OPINION      = 'opinion';
    public const OPINIONTO    = 'opinion';
    public const PAGE         = 'page';
    public const VIDEO        = 'video';
    public const PRESS_RLS    = 'press_rls';
    public const INFOGRAPHICS = 'infographics';

    public const TYPES = [
        1 => self::NEWS,
        2 => self::ARTICLE,
        3 => self::INTERVIEW,
        4 => self::PERSON,
        5 => self::COMPANY,
        6 => self::OPINION,
        7 => self::VIDEO,
        8 => self::PAGE,
    ];

    public const TYPES_CAT = [
        self::PRESS_RLS,
        self::INFOGRAPHICS,
    ];

    private const ROUTE_CODE_ALIASES = [
        'opinion' => 'opinions',
    ];

    private const CACHE_ALL = 'article_types_all';

    private const CACHE_ACTIVE_HOMEPAGE_CODES = 'article_types_active_homepage_codes';

    private const CACHE_HOMEPAGE_DROPDOWN_PREFIX = 'article_types_homepage_dropdown';

    private const CACHE_CONTENT_SPLIT_ENABLED_CODES = 'article_types_content_split_enabled_codes';

    protected $fillable = [
        'code',
        'is_active',
        'homepage',
        'is_splittable',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'homepage' => 'boolean',
        'is_splittable' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Apply scope for select2 queries in Backpack (only active types)
        static::addGlobalScope('active_for_select', function ($query) {
            // Only apply scope when used in select2 fields (not in CRUD list)
            if (request()->is('admin/article*') && !request()->is('admin/article-type*')) {
                $query->where('is_active', true)->orderBy('code');
            }
        });

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    public function articles()
    {
        return $this->hasMany(\App\Models\Articles\Article::class, 'type_id');
    }

    /**
     * Get article type display name for Backpack CRUD and select2 fields.
     * This accessor is specifically for admin panel usage.
     * Falls back to code if translation is not available.
     */
    public function getDisplayNameAttribute(): string
    {
        // The Translatable package automatically uses the current locale
        return $this->name ?? $this->code ?? '';
    }

    // ==============================
    // Caching methods
    // ==============================


    /**
     * Получить все типы статей из кэша
     *
     * @return \Illuminate\Support\Collection
     */
    public static function allCached()
    {
        return Cache::rememberForever(self::CACHE_ALL, function () {
            return static::query()
                ->with('translations')
                ->get()
                ->keyBy('id');
        });
    }


    /**
     *  Locales codes for Homepage  cached
     *
     * @return array
     */
    public static function activeHomepageCodesCached(): array
    {
        return Cache::rememberForever(self::CACHE_ACTIVE_HOMEPAGE_CODES, function () {
            return static::query()
                ->where('is_active', true)
                ->where('homepage', true)
                ->orderBy('code')
                ->pluck('code')
                ->map(fn (string $code) => static::codeForRoute($code))
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        });
    }

    public static function contentSplitEnabledCodes(): array
    {
        return Cache::rememberForever(self::CACHE_CONTENT_SPLIT_ENABLED_CODES, function () {
            return static::query()
                ->where('is_splittable', true)
                ->orderBy('code')
                ->pluck('code')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        });
    }


    /**
     * @param bool $categories
     * @return string
     */
    public static function getForRoute(bool $categories = false): string
    {
        if ($categories) {
            return implode('|', self::TYPES_CAT);
        }

        return implode('|', static::activeHomepageCodesCached());
    }


    /**
     * @return array
     */
    public static function getAsArray(): array
    {
        return static::activeHomepageCodesCached();
    }


    /**
     *  Types for Homepage dropdown cached
     *
     * @param string $locale
     * @return Collection
     */
    public static function homepageDropdownCached(string $locale): Collection
    {
        return Cache::rememberForever(self::homepageDropdownCacheKey($locale), function () use ($locale) {
            return static::query()
                ->where('is_active', true)
                ->where('homepage', true)
                ->withTranslation($locale)
                ->orderBy('code')
                ->get();
        });
    }


    /**
     * @return array
     */
    private static function homepageDropdownCacheLocales(): array
    {
        return Locale::getActive()
            ->pluck('code')
            ->push(app()->getLocale())
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * @param string $locale
     * @return string
     */
    private static function homepageDropdownCacheKey(string $locale): string
    {
        return self::CACHE_HOMEPAGE_DROPDOWN_PREFIX.'_'.$locale;
    }

    /**
     * Получить тип статьи по id из кэша
     *
     * @param int $id
     * @return static|null
     */
    public static function findCached(int $id): static|null
    {
        return static::allCached()->get($id);
    }

    /**
     * Очистить кэш (если кто-то редактировал тип)
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_ALL);
        Cache::forget(self::CACHE_ACTIVE_HOMEPAGE_CODES);
        Cache::forget(self::CACHE_CONTENT_SPLIT_ENABLED_CODES);

        foreach (self::homepageDropdownCacheLocales() as $locale) {
            Cache::forget(self::homepageDropdownCacheKey($locale));
        }
    }

    public static function getTypeId(string $type): ?int
    {
        $key = array_search($type, self::TYPES, true);
        return $key === false ? null : $key;
    }

    public static function codeForRoute(string $code): string
    {
        return self::ROUTE_CODE_ALIASES[$code] ?? $code;
    }

    public static function codeFromRoute(string $routeCode): string
    {
        return array_flip(self::ROUTE_CODE_ALIASES)[$routeCode] ?? $routeCode;
    }
}
