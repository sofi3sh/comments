<?php

namespace App\Models\Articles;

use App\Models\Traits\BelongsToSite;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ArticlesBlockSetting extends Model
{
    use CrudTrait;
    use BelongsToSite;

    protected $table = 'articles_block_settings';

    protected $fillable = [
        'site_id',
        'block_key',
        'is_active',
        'limit',
        'order_by',
        'order_direction',
        'views_window_hours',
        'refresh_interval_hours',
        'category_id',
        'type_id',
        'author_role_ids',
        'marker_ids',
        'published_at_from',
        'published_at_to',
        'updated_at_from',
        'updated_at_to',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'category_id' => 'integer',
        'type_id' => 'integer',
        'is_active' => 'boolean',
        'limit' => 'integer',
        'views_window_hours' => 'integer',
        'refresh_interval_hours' => 'integer',
        'author_role_ids' => 'array',
        'marker_ids' => 'array',
        'published_at_from' => 'datetime',
        'published_at_to' => 'datetime',
        'updated_at_from' => 'datetime',
        'updated_at_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const MAIN_CONTAINER_RIGHT = 'main-container-right';
    public const MAIN_CONTAINER_LEFT  = 'main-container-left';
    public const SWIPER_CONTAINER     = 'swiper-container';
    public const ARTICLES_CONTAINER_LEFT  = 'articles-container-left';
    public const ARTICLES_CONTAINER_RIGHT = 'articles-container-right';
    public const LATEST_MATERIALS = 'latest-materials';
    public const VIDEO_MATERIALS  = 'video-materials';

    public const AVAILABLE_BLOCKS = [
        self::MAIN_CONTAINER_RIGHT,
        self::MAIN_CONTAINER_LEFT,
        self::SWIPER_CONTAINER,
        self::ARTICLES_CONTAINER_LEFT,
        self::ARTICLES_CONTAINER_RIGHT,
        self::LATEST_MATERIALS,
        self::VIDEO_MATERIALS,
    ];

    public static function cacheKeyForSite(int $siteId): string
    {
        return "articles_block_settings:site_id:{$siteId}";
    }

    protected static function booted()
    {
        static::saved(function ($model) {
            Cache::forget(static::cacheKeyForSite((int) $model->site_id));
        });

        static::updated(function ($model) {
            Cache::forget(static::cacheKeyForSite((int) $model->site_id));
        });

        static::deleted(function ($model) {
            Cache::forget(static::cacheKeyForSite((int) $model->site_id));
        });
    }

    public static function getMaxViewPeriod(): int
    {
        return (int)config('views.days_after_publication', 7) * 24;
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Site\Site::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function articleType(): BelongsTo
    {
        return $this->belongsTo(ArticleType::class, 'type_id');
    }

    public function markerModels()
    {
        $ids = $this->marker_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return Marker::query()
            ->whereIn('id', $ids)
            ->get();
    }

    public function tagModels()
    {
        $ids = $this->tag_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return Tag::query()
            ->whereIn('id', $ids)
            ->get();
    }
}