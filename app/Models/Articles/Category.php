<?php

namespace App\Models\Articles;

use App\Models\Settings\Locale;
use App\Models\Traits\BelongsToSite;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Kalnoy\Nestedset\NodeTrait;
use App\Models\Site\Site;
use App\Models\Seo\SeoMeta;
use App\Models\Traits\GetAvailableLocales;
use OwenIt\Auditing\Contracts\Auditable;

class Category extends Model implements TranslatableContract, Auditable
{
    private const CACHE_TYPE_DROPDOWN_CATEGORIES_PREFIX = 'categories_type_dropdown';

    use CrudTrait;
    use HasFactory;
    use NodeTrait;
    use GetAvailableLocales;
    use Translatable;
    use \OwenIt\Auditing\Auditable;
//    use BelongsToSite;  //todo

    /** @var list<string> Slug is on main table (categories.slug), not in translations for create/fill. */
    public array $translatedAttributes = [
        'name',
        'title',
        'keywords',
    ];

    protected string $translationModel = \App\Models\Articles\Translate\CategoryTranslation::class;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'id',
        'site_id',
        'parent_id',
        'slug',     // todo  not used
        'homepage',
        'subdomain',
        'created_at',
        'updated_at',
    ];

    protected $guarded = ['id', '_lft', '_rgt', '_depth'];


    protected static function booted(): void
    {
        parent::booted();

        static::saved(fn () => static::clearCache());
        static::updated(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    /**
     * Get the name of the "left" field.
     *
     * @return string
     */
    public function getLftName(): string
    {
        return '_lft';
    }

    /**
     * Get the name of the "right" field.
     *
     * @return string
     */
    public function getRgtName(): string
    {
        return '_rgt';
    }

    /**
     * Get the name of the "parent" field.
     *
     * @return string
     */
    public function getParentIdName(): string
    {
        return 'parent_id';
    }

    /**
     * Get the name of the "depth" field.
     *
     * @return string
     */
    public function getDepthName(): string
    {
        return '_depth';
    }

    protected $casts = [
        'site_id' => 'integer',
        'parent_id' => 'integer',
    ];

    protected $auditInclude = [
        'site_id',
        'parent_id',
        'slug',
        'homepage',
        'subdomain',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'entity');
    }


    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the root categories
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function roots(): \Illuminate\Database\Eloquent\Builder
    {
        return static::whereIsRoot();
    }

    /**
     * Get the available locales with icons
     *
     * @return string
     */
    public function getAvailableLocalesAttribute(): string
    {
        $localeCodes = $this->translations->pluck('locale')->toArray();

        return $this->getAvailableLocalesHtml($localeCodes);
    }

    /**
     * Get the available SEO locales with icons
     *
     * @return string
     */
    public function getSeoAvailableLocalesAttribute(): string
    {
        $localeCodes = $this->seoMeta?->translations?->pluck('locale')?->toArray() ?? [];

        return $this->getAvailableLocalesHtml($localeCodes);
    }
    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get category display name for Backpack CRUD and select2 fields.
     * This accessor is specifically for admin panel usage.
     * Falls back to slug if translation is not available.
     */
    public function getDisplayNameAttribute(): string
    {
        // The Translatable package automatically uses the current locale
        return $this->name ?? $this->attributes['slug'] ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    public static function getFieldSeoPrefix(): string
    {
        return 'category-seo';
    }


    // ==============================
    // CACHE
    // ==============================

    public static function allCached()
    {
        return Cache::rememberForever('categories_all', function () {
            return static::query()
                ->with(['translations', 'seoMeta.translations'])
                ->get()
                ->keyBy('id');
        });
    }

    public static function allForRoute()
    {
        return Cache::rememberForever('categories_route', function () {
            $categories = static::query()
                ->pluck('slug')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            return implode('|', $categories);
        });
    }

    public static function allForSite()
    {
        return Cache::rememberForever('categories_site', function () {
            $categories = static::query()
                ->pluck('slug')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            return $categories;
        });
    }

    public static function typeDropdownCategoriesCached(string $locale): Collection
    {
        return Cache::rememberForever(self::typeDropdownCategoriesCacheKey($locale), function () use ($locale) {
            return static::query()
                ->whereIn('slug', ArticleType::TYPES_CAT)
                ->withTranslation($locale)
                ->get()
                ->sortBy(fn (Category $category) => array_search($category->slug, ArticleType::TYPES_CAT, true))
                ->values();
        });
    }

    private static function typeDropdownCategoriesCacheKey(string $locale): string
    {
        return self::CACHE_TYPE_DROPDOWN_CATEGORIES_PREFIX.'_'.$locale;
    }

    public static function clearCache(): void
    {
        Cache::forget('categories_all');
        Cache::forget('categories_route');
        Cache::forget('categories_site');

        foreach (Locale::getAvailableAsArr('code') as $locale) {
            Cache::forget(self::typeDropdownCategoriesCacheKey($locale));
        }
    }

    public function getSite()
    {
        return Site::where('slug', $this->slug)
            ->first();
    }
}
