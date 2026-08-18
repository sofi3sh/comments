<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\ArticlesBlockSetting;
use App\Models\Settings\Locale;
use App\Models\Site\Site;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ArticlesBlockSettingsService
{
    public const ORDER_BY_PUBLISHED  = 'published_at';
    public const ORDER_BY_UPDATED    = 'updated_at';
    public const ORDER_BY_VIEWS      = 'views';
    private array $settingsCache = [];

    /**
     * Повертає матеріали для блоку з потрібним форматом результату.
     *
     * Для сторінки колекції зберігається пагінатор, а головна сторінка
     * отримує обмежений список без дорогого підрахунку всіх записів.
     */
    public function getArticlesForBlock(
        string $blockKey,
        int $fallbackLimit,
        bool $withPagination = true
    )
    {
        $siteId = $this->getSiteId(); //TODO

        if ($siteId <= 0) {
            $siteId = (int) Site::query()->value('id');
        }

        $locale = app()->getLocale();
        $cacheKey = $this->makeCacheKey($siteId, $blockKey, $locale, $withPagination);
        $setting = $this->getSetting($siteId, $blockKey);

        return Cache::remember(
            $cacheKey,
            $this->resolveCacheTtlFromSetting($setting),
            function () use ($siteId, $blockKey, $fallbackLimit, $setting, $locale, $withPagination) {

                if (! $setting) {
                    $query = Article::query()
                        ->forMainContainer($fallbackLimit)
                        ->with($this->cardRelations($locale));

                    return $this->getBlockResults($query, $fallbackLimit, $withPagination);
                }

                if (! $setting->is_active) {
                    return new Collection();
                }

                $limit = (int) ($setting->limit ?? $fallbackLimit);
                $direction = $this->normalizeDirection((string) ($setting->order_direction ?? ''));
                $orderBy = trim((string) ($setting->order_by ?? ''));

                $query = Article::query()
                    ->published()
                    ->whereHas('translations', function ($q) use ($locale) {
                        $q->where('locale', $locale);
                    })
                    // Дані карток завантажуємо пакетом, щоб не створювати N+1 під час рендерингу.
                    ->with($this->cardRelations($locale));
                $this->applyCategoryAndTypeFilters($query, $setting);
                $this->applyAuthorRoleFilters($query, $setting);
                $this->applyMarkerFilters($query, $setting);
                $this->applyPublishedAndUpdatedRanges($query, $setting);

                // Період переглядів обмежує вік публікацій лише для рейтингу за переглядами.
                if ($orderBy === self::ORDER_BY_VIEWS) {
                    $this->applyViewPeriodFilter($query, $setting);
                }

                if ($orderBy === self::ORDER_BY_VIEWS && ! $withPagination) {
                    return $this->getViewRankedBlockResults($query, $limit, $direction, $setting);
                }

                $query = $this->applyOrdering($query, $orderBy, $direction, $setting);

                return $this->getBlockResults($query, $limit, $withPagination);
            }
        );
    }

    private function getSiteId()
    {
        return app('currentSite')->id;
//        return 1;     // TODO if you want to see many results for test
    }

    private function getSetting(int $siteId, string $blockKey): ?ArticlesBlockSetting
    {
        if ($siteId <= 0) {
            return null;
        }

        if (empty($this->settingsCache)) {
            $this->settingsCache = Cache::rememberForever(
                ArticlesBlockSetting::cacheKeyForSite($siteId),
                function () use ($siteId) {
                    return ArticlesBlockSetting::query()
                        ->where('site_id', $siteId)
                        ->get()
                        ->keyBy('block_key')
                        ->toArray();
                }
            );
        }

        $setting = $this->settingsCache[$blockKey] ?? null;

        if ($setting && is_array($setting)) {
            $setting = new ArticlesBlockSetting((array) $setting);
        }

        return $setting;
    }

    /**
     * Формує окремий ключ кешу для списку та пагінатора.
     */
    private function makeCacheKey(int $siteId, string $blockKey, string $locale, bool $withPagination): string
    {
        $blockKey = trim($blockKey);

        $resultType = $withPagination ? 'paginator' : 'list';

        return "articles_block:site_id:{$siteId}:locale:{$locale}:block_key:{$blockKey}:result:{$resultType}";
    }

    /**
     * Видаляє кеш блоку для всіх локалей і обох форматів результату.
     */
    public function forgetArticlesForBlockCache(int $siteId, string $blockKey): void
    {
        foreach (Locale::getAll()->pluck('code')->filter() as $locale) {
            Cache::forget($this->makeCacheKey($siteId, $blockKey, $locale, true));
            Cache::forget($this->makeCacheKey($siteId, $blockKey, $locale, false));
        }
    }

    /**
     * Виконує запит у форматі, потрібному поточній сторінці.
     */
    private function getBlockResults(Builder $query, int $limit, bool $withPagination): mixed
    {
        if ($withPagination) {
            return $query->paginate($limit);
        }

        // На головній пагінація не відображається, тому COUNT(*) не потрібен.
        return $query->limit($limit)->get();
    }

    /**
     * Повертає рейтинг за переглядами та за потреби доповнює його новими статтями.
     */
    private function getViewRankedBlockResults(
        Builder $query,
        int $limit,
        string $direction,
        ArticlesBlockSetting $setting
    ): Collection {
        // Спочатку ранжуємо лише статті з переглядами у потрібному вікні.
        // Це не змушує MySQL групувати весь масив опублікованих статей.
        $articles = $this->applyViewsOrdering(clone $query, $direction, $setting, onlyViewed: true)
            ->limit($limit)
            ->get();

        if ($articles->count() >= $limit) {
            return $articles;
        }

        // Статті без переглядів зберігають участь у блоці: добираємо їх за свіжістю.
        $missing = $limit - $articles->count();
        $fallback = (clone $query)
            ->when(
                $articles->isNotEmpty(),
                fn (Builder $fallback) => $fallback->whereNotIn('articles.id', $articles->modelKeys())
            )
            ->orderByDesc('published_at')
            ->limit($missing)
            ->get();

        // Явно об'єднуємо моделі, щоб колекція не містила вкладених масивів.
        return new Collection([
            ...$articles->all(),
            ...$fallback->all(),
        ]);
    }

    /**
     * Описує зв'язки картки для пакетного завантаження без N+1 запитів.
     */
    private function cardRelations(string $locale): array
    {
        return [
            'translations' => function ($query) use ($locale) {
                $query->where('locale', $locale)
                    ->select([
                        'id', 'article_id', 'locale', 'title', 'title_with_markers',
                        'excerpt', 'slug',
                    ]);
            },
            'type:id,code',
            'category:id,slug',
            'authors.translations',
            'thumbnailAttachment.sizes',
            'meta' => function ($query) {
                $query->where('field', 'youtube_id')
                    ->whereNull('locale')
                    ->select(['id', 'article_id', 'field', 'value', 'locale']);
            },
        ];
    }

    private function resolveCacheTtlFromSetting(?ArticlesBlockSetting $setting): \DateTimeInterface
    {
        if (app()->environment('local')) {
            return now()->addSeconds(env('LOCAL_CACHE_TTL_FROM_SETTING', 30));
        }

        $hours = max(1, (int) ($setting?->refresh_interval_hours ?? config('views.main_page_refresh_cache_ttl')));

        return now()->addHours($hours);
    }

    private function applyCategoryAndTypeFilters(Builder $query, ArticlesBlockSetting $setting): void
    {
        if (! empty($setting->category_id)) {
            $query->where('category_id', (int) $setting->category_id);
        }

        if (! empty($setting->type_id)) {
            $query->where('type_id', (int) $setting->type_id);
        }
    }

    private function applyAuthorRoleFilters(Builder $query, ArticlesBlockSetting $setting): void
    {
        $roleIds = $setting->author_role_ids ?? [];
        $roleIds = array_values(array_filter($roleIds, static fn ($v) => $v !== null && $v !== ''));

        if (empty($roleIds)) {
            return;
        }

        $query->whereHas('authors', function ($q) use ($roleIds) {
            $q->whereHas('roles', function ($roleQuery) use ($roleIds) {
                $roleQuery->whereIn('id', $roleIds);
            });
        });
    }

    private function applyMarkerFilters(Builder $query, ArticlesBlockSetting $setting): void
    {
        $markerIds = $setting->marker_ids ?? [];
        $markerIds = array_values(array_filter($markerIds, static fn ($v) => $v !== null && $v !== ''));

        if (empty($markerIds)) {
            return;
        }

        $query->whereHas('markers', function ($q) use ($markerIds) {
            $q->whereIn('markers.id', $markerIds);
        });
    }

    private function applyPublishedAndUpdatedRanges(Builder $query, ArticlesBlockSetting $setting): void
    {
        // Поки не видаляю, на майбутнє можливо пригодиться
        // if ($setting->published_at_from) {
        //     $query->where('published_at', '>=', $setting->published_at_from);
        // }
        //
        // if ($setting->published_at_to) {
        //     $query->where('published_at', '<=', $setting->published_at_to);
        // }
        //
        // if ($setting->updated_at_from) {
        //     $query->where('updated_at', '>=', $setting->updated_at_from);
        // }
        //
        // if ($setting->updated_at_to) {
        //     $query->where('updated_at', '<=', $setting->updated_at_to);
        // }

        $now = Carbon::now();

        $this->applyRollingWindow($query, 'published_at', $setting->published_at_from, $setting->published_at_to, $now);
        $this->applyRollingWindow($query, 'updated_at', $setting->updated_at_from, $setting->updated_at_to, $now);
    }

    private function applyRollingWindow(
        Builder $query,
        string $column,
        ?Carbon $from,
        ?Carbon $to,
        Carbon $now
    ): void {
        if (! $from && ! $to) {
            return;
        }

        if ($from && $to) {
            $seconds = $to->diffInSeconds($from, false);
            if ($seconds <= 0) {
                return;
            }

            $start = (clone $now)->subSeconds($seconds);
            $query->whereBetween($column, [$start, $now]);

            return;
        }

        if ($from) {
            $seconds = $now->diffInSeconds($from, false);
            if ($seconds <= 0) {
                return;
            }

            $start = (clone $now)->subSeconds($seconds);
            $query->whereBetween($column, [$start, $now]);
        }
    }

    private function applyViewPeriodFilter(Builder $query, ArticlesBlockSetting $setting): void
    {
        if (! $setting->views_window_hours) {
            return;
        }

        $hours = (int) $setting->views_window_hours;
        if ($hours <= 0) {
            return;
        }

        $query->where('published_at', '>=', Carbon::now()->subHours($hours));
    }

    private function applyOrdering(Builder $query, string $orderBy, string $direction, ArticlesBlockSetting $setting): Builder
    {
        $orderBy = trim($orderBy);
        if ($orderBy === '') {
            return $query;
        }

        if ($direction === '') {
            return $query;
        }

        return match ($orderBy) {
            self::ORDER_BY_PUBLISHED => $query->orderBy('published_at', $direction),
            self::ORDER_BY_UPDATED   => $query->orderBy('updated_at', $direction),
            self::ORDER_BY_VIEWS     => $this->applyViewsOrdering($query, $direction, $setting),
            default => $query,
        };
    }

    private function applyViewsOrdering(
        Builder $query,
        string $direction,
        ArticlesBlockSetting $setting,
        bool $onlyViewed = false
    ): Builder {
        $defaultHoursLimit = ArticlesBlockSetting::getMaxViewPeriod();

        $hours = (int) ($setting->views_window_hours ?? $defaultHoursLimit);

        if ($hours <= 0) {
            $hours = $defaultHoursLimit;
        }

        $from = now()->subHours($hours);

        if (! $onlyViewed) {
            // Пагінована сторінка зберігає попередню поведінку:
            // статті без переглядів залишаються у видачі з нульовим рейтингом.
            return $query
                ->leftJoin('article_views', function ($join) use ($from) {
                    $join->on('articles.id', '=', 'article_views.article_id')
                        ->where('article_views.date_hour', '>=', $from);
                })
                ->select('articles.*')
                ->selectRaw('COALESCE(SUM(article_views.views), 0) as views_sum')
                ->groupBy('articles.id')
                ->orderBy('views_sum', $direction);
        }

        // Головна ранжує лише статті з переглядами за період, щоб не групувати всі публікації.
        $viewTotals = DB::table('article_views')
            ->where('date_hour', '>=', $from)
            ->select('article_id')
            ->selectRaw('SUM(views) as views_sum')
            ->groupBy('article_id');

        return $query
            ->joinSub($viewTotals, 'article_views_period', function ($join) {
                $join->on('articles.id', '=', 'article_views_period.article_id');
            })
            ->select('articles.*')
            ->addSelect('article_views_period.views_sum')
            ->orderBy('article_views_period.views_sum', $direction);
    }

    private function normalizeDirection(string $direction): string
    {
        $direction = strtolower(trim($direction));

        if ($direction === 'asc') {
            return 'asc';
        }

        if ($direction === 'desc') {
            return 'desc';
        }

        return '';
    }
}
