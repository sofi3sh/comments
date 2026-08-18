<?php

namespace App\Models\Articles;

use App\Contracts\LocalizedUrlContract;
use App\Models\Scopes\CurrentSiteScope;
use App\Models\Settings\Locale;
use App\Models\Traits\ArticleCrudPresenter;
use App\Models\Traits\GetAvailableLocales;
use App\Services\Article\ArticleUrlBuilder;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;

class Article extends Model implements TranslatableContract, LocalizedUrlContract, Auditable
{
    use CrudTrait;
    use GetAvailableLocales;
    use HasFactory;
    use Translatable;
    use ArticleCrudPresenter;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /** @var list<string> */
    public array $translatedAttributes = [
        'title',
        'title_with_markers',
        'excerpt',
        'content',
        'content_html',
        'slug'
    ];

    protected string $translationModel = \App\Models\Articles\Translate\ArticleTranslation::class;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'id',
        'old_id',
        'category_id',
        'type_id',
        'status',
        'published_at',
        'source_url',
        'views',
        'is_media',
        'do_follow',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'type_id' => 'integer',
        'views' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $auditInclude = [
        'category_id',
        'type_id',
        'status',
        'published_at',
        'source_url',
        'is_media',
        'do_follow',
    ];

    /*
    |--------------------------------------------------------------------------
    | CONSTANTS
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_MODERATION = 'moderation';

    public const PREFIX_ROUTE = 'article';

    public const ARTICLE_TYPE_IDS = [
        1,
        2,
        3,
    ];

    public static function getTypeOptions(): array
    {
        return \App\Models\Articles\ArticleType::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function ($type) {
                return [$type->id => $type->name];
            })
            ->toArray();
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => __('article.fields.statuses.draft'),
            self::STATUS_PENDING => __('article.fields.statuses.pending'),
            self::STATUS_PUBLISHED => __('article.fields.statuses.published'),
            self::STATUS_REJECTED => __('article.fields.statuses.rejected'),
            self::STATUS_MODERATION => __('article.fields.statuses.moderation'),
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::updated(function (self $article) {
            $article->forgetContentCacheAfterCommit();
        });

        static::deleting(function (self $article) {
            $article->forgetContentCacheAfterCommit($article->getContentCacheLocales());

            if (! $article->isForceDeleting()) {
                $article->status = self::STATUS_MODERATION;
                $article->saveQuietly();

                $article->translations()
                    ->get()
                    ->each
                    ->delete();
            }

            // A soft delete does not delete translations, so Scout does not receive
            // their model events automatically. Remove them before either soft or
            // hard deletion to keep Meilisearch free of orphaned documents.
            $article->translations()->get()->each->unsearchable();
        });

        static::restoring(function (self $article) {
            $article->status = self::STATUS_MODERATION;
        });

        static::restored(function (self $article) {
            $article->translations()
                ->onlyTrashed()
                ->get()
                ->each
                ->restore();
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentSiteScope());
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(\App\Models\Articles\Category::class);
    }

    public function type()
    {
        return $this->belongsTo(\App\Models\Articles\ArticleType::class, 'type_id');
    }

    public function authors()
    {
        return $this->belongsToMany(\App\Models\User\User::class, 'article_authors')
            ->withTrashed();
    }

    public function editors()
    {
        return $this->belongsToMany(\App\Models\User\User::class, 'article_editors');
    }

    public function tags()
    {
        return $this->belongsToMany(\App\Models\Articles\Tag::class, 'article_tags');
    }

    public function markers()
    {
        return $this->belongsToMany(\App\Models\Articles\Marker::class, 'article_marker');
    }

    public function sites()
    {
        return $this->belongsToMany(\App\Models\Site\Site::class, 'article_sites');
    }

    public function seoMeta()
    {
        return $this->morphOne(\App\Models\Seo\SeoMeta::class, 'entity');
    }

    public function meta()
    {
        return $this->hasMany(\App\Models\Articles\ArticleMeta::class, 'article_id');
    }

    public function attachments()
    {
        return $this->morphToMany(
            Attachment::class,
            'owner',
            'article_attachments'
        )
            ->withPivot([
                'type',
                'order',
            ])
            ->withTimestamps();
    }

    public function thumbnailAttachment()
    {
        return $this->morphToMany(
            Attachment::class,
            'owner',
            'article_attachments'
        )
            ->whereNull('attachments.parent_id')
            ->wherePivot('type', Attachment::THUMBNAIL_TYPE)
            ->withPivot([
                'type',
                'order',
            ])
            ->withTimestamps();
    }

    public function articleViews(): hasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    public function articleViewsAggregated(): hasMany
    {
        return $this->hasMany(ArticleView::class)
            ->selectRaw('article_id, date_hour, SUM(views) as views')
            ->groupBy('article_id', 'date_hour')
            ->orderBy('date_hour')
            ->addSelect('article_id');
    }

    public function contentChecks(): hasMany
    {
        return $this->hasMany(ArticleContent::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ArticleActivityLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeForSignificantList($query, ?int $typeId, ?string $locale = null, ?string $letter = null): \Illuminate\Database\Eloquent\Builder
    {
        $locale = $locale ?? app()->getLocale();

        $query->where('status', self::STATUS_PUBLISHED);

        if ($typeId !== null) {
            $query->where('type_id', $typeId);
        }

        $query->with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale)
                ->select('id', 'article_id', 'locale', 'title', 'excerpt', 'content', 'content_html', 'slug');
        }])
            ->select('id', 'old_id', 'type_id', 'views', 'published_at', 'created_at', 'updated_at');

        if ($letter !== null) {
            $query->whereHas('translations', function ($q) use ($locale, $letter) {
                $q->where('locale', $locale)
                    ->where('title', 'LIKE', $letter.'%');
            });
        }

        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope a query to only include published articles (all types).
     *
     * Articles only for chosen locale
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForMainContainer($query, int $limit = 4, ?string $locale = null): \Illuminate\Database\Eloquent\Builder
    {
        $locale ??= app()->getLocale();

        return $query->whereIn('type_id', self::ARTICLE_TYPE_IDS)
            ->published()
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale);
            })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale)
                        ->select(
                            'id',
                            'article_id',
                            'locale',
                            'title',
                            'excerpt',
                            'slug'
                        );
                },
                'authors',
                'type',
                'category',
            ])
            ->latest()
            ->limit($limit);
    }

    public function scopeByIdOrOldId($query, int $id): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where(function ($q) use ($id) {
                $q->where('old_id', $id)->orWhere('id', $id);
            })
            ->orderByRaw('old_id IS NULL ASC')
            ->limit(1);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public function getCoverUrl(?string $size = null): string
    {
        $cover = $this->getThumbnailAttachment();
        if (! $cover) {
            return asset(config('app.default_cover'));
        }

        if ($size) {
            return $cover->getSizeUrl($size);
        }

        return Storage::disk('public')->url($cover->path);
    }

    public function getCoverSrcset(array $sizes = ['card_sm', 'card_lg', 'cover']): string
    {
        /** @var Attachment $cover */
        $cover = $this->getThumbnailAttachment();
        if (! $cover) {
            return '';
        }

        return $cover->getSrcset($sizes);
    }

    /**
     * Повертає eager-loaded обкладинку або завантажує її одним запитом за потреби.
     */
    private function getThumbnailAttachment(): ?Attachment
    {
        // Використовуємо eager-loaded relation, якщо вона вже є для картки.
        if ($this->relationLoaded('thumbnailAttachment')) {
            return $this->thumbnailAttachment->first();
        }

        return $this->thumbnailAttachment()->first();
    }

    public function forgetContentCache(?string $locale = null): void
    {
        $locales = $locale
            ? [$locale]
            : $this->getContentCacheLocales();

        foreach ($locales as $locale) {
            Cache::forget(articleContentCacheKey($this, 'first', $locale));
            Cache::forget(articleContentCacheKey($this, 'rest', $locale));
            Cache::forget(articleContentCacheKey($this, 'full', $locale));
        }
    }

    private function forgetContentCacheAfterCommit(?array $locales = null): void
    {
        $locales ??= $this->getContentCacheLocales();

        DB::afterCommit(function () use ($locales): void {
            if (empty($locales)) {
                $this->forgetContentCache();

                return;
            }

            foreach (array_unique($locales) as $locale) {
                $this->forgetContentCache($locale);
            }
        });
    }

    private function getContentCacheLocales(): array
    {
        $locales = $this->relationLoaded('translations')
            ? $this->translations->pluck('locale')->all()
            : $this->translations()->pluck('locale')->all();

        return ! empty($locales)
            ? array_values(array_unique($locales))
            : [app()->getLocale()];
    }


    /**
     * Get the available locales
     */
    public function getAvailableLocalesAttribute(): string
    {
        $localeCodes = $this->translations->pluck('locale')->toArray();

        return $this->getAvailableLocalesHtml($localeCodes);
    }

    /**
     * Get the available SEO locales
     */
    public function getSeoAvailableLocalesAttribute(): string
    {
        $localeCodes = $this->seoMeta?->translations?->pluck('locale')?->toArray() ?? [];

        return $this->getAvailableLocalesHtml($localeCodes);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return __('article.fields.statuses.'.$this->status);
    }

    /**
     * Get type label (name) from type_id
     */
    public function getTypeLabelAttribute(): string
    {
        if (! $this->type_id) {
            return '-';
        }

        if ($this->relationLoaded('type')) {
            return $this->type ? $this->type->name : '-';
        }

        $articleType = ArticleType::find($this->type_id);

        return $articleType ? $articleType->name : '-';
    }

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    //todo   what for?
    public static function getFieldSeoPrefix(): string
    {
        return 'article-seo';
    }

    public function getArticleUrl(): string
    {
        return app(ArticleUrlBuilder::class)->urlForLocale($this, app()->getLocale());
    }


    /**
     * for LanguageSwitcherBuilder
     *
     * @return Collection
     */
    public function getAvailableLocales(): Collection
    {
        $availableLocales = Locale::getActive();

        $articleLocales = $this->translations
            ->pluck('locale')
            ->toArray();

        return $availableLocales->filter(function ($locale) use ($articleLocales) {
            return in_array($locale->code, $articleLocales);
        });
    }


    /**
     *  for LanguageSwitcherBuilder
     *
     * @param string $locale
     * @return string
     */
    public function getItemUrlForLocale(string $locale): string
    {
        return $this->getArticleUrlForLocale($locale);
    }

    public function getArticleUrlForLocale(string $locale): string
    {
        return app(ArticleUrlBuilder::class)->urlForLocale($this, $locale);
    }
}
