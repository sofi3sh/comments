<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Support\RedisConst;

class ArticleViewsService
{
    /**
     * @return int
     */
    public function processStream(): int
    {
        $lockKey = RedisConst::CRON_VIEWS_RUN;
        $lockKeyTtl = config('views.lock_key_ttl');
        $stream = RedisConst::STREAM;
        $streamProcess = RedisConst::PROCESSING_STREAM;
        $streamsPrefix = config('database.redis.options.prefix');
        $batchLimit = config('views.batch_limit');

        $running = Redis::set($lockKey, 1, 'EX', $lockKeyTtl, 'NX');
        if (!$running) return 0;

        try {
            if (!Redis::exists($streamProcess)) {
                try {
                    Redis::rename($stream, $streamProcess);
                } catch (\RedisException $e) {
                    Redis::del($lockKey);
                    return 0;
                }
            }

            $lastId = '0-0';
            $views = [];
            $viewsLocale = [];
            $viewsLocaleHourly = [];
            $total = 0;

            while ($batch = Redis::xread([$streamProcess => $lastId], $batchLimit)) {

                Redis::expire($lockKey, $lockKeyTtl);
                $streamProcessTitle = $streamsPrefix . $streamProcess;

                foreach ($batch[$streamProcessTitle] as $id => $data) {

                    $article = $data['a'] ?? null;
                    $locale  = $data['l'] ?? null;

                    if (!$article || !$locale) continue;

                    // timestamp из ID Redis (мс -> сек)
                    [$ms, ] = explode('-', $id);
                    $timestamp = (int)($ms / 1000);

                    $dateHour = gmdate('Y-m-d H:00:00', $timestamp);

                    // common article views
                    $views[$article] = ($views[$article] ?? 0) + 1;
                    // article views by locale
                    $viewsLocale[$article][$locale] = ($viewsLocale[$article][$locale] ?? 0) + 1;
                    // article views by locale and hour
                    $viewsLocaleHourly[$article][$locale][$dateHour] =
                        ($viewsLocaleHourly[$article][$locale][$dateHour] ?? 0) + 1;

                    $lastId = $id;
                    $total++;
                }
            }

            if (empty($views)) return 0;

            try {
                $this->saveToDatabase($views, $viewsLocale, $viewsLocaleHourly);
            } catch (\Throwable $e) {
                \Log::error('Views processing failed', [
                    'error' => $e->getMessage()
                ]);

                return 0;
            }

            $this->syncRedisViews($views);

            Redis::del($streamProcess);

            return $total;

        } finally {
            Redis::del($lockKey);
        }
    }


    /**
     * @param array $views
     * @param array $viewsLocale
     * @param array $viewsLocaleHourly
     * @return void
     */
    protected function saveToDatabase(array &$views, array &$viewsLocale, array &$viewsLocaleHourly): void
    {
        // -------------------------------
        // 1. article_views (hourly) — last dayBack only
        // -------------------------------
        $daysBack = config('views.days_after_publication'); // сколько дней назад брать
        $dateFrom = now()->subDays($daysBack)->toDateTimeString();

        $articleIdsList = array_keys($views);

        $validArticleIds = DB::table('articles')
            ->whereIn('id', $articleIdsList)
            ->where('published_at', '>', $dateFrom)
            ->pluck('id')
            ->all();

        $validMap = array_flip($validArticleIds);

        unset($articleIdsList);
        unset($validArticleIds);

        // фильтруем hourly views только по валидным статьям
        $filteredHourly = array_intersect_key($viewsLocaleHourly, $validMap);

        $rows = [];
        foreach ($filteredHourly as $articleId => $locales) {
            foreach ($locales as $locale => $hours) {
                foreach ($hours as $dateHour => $count) {
                    $rows[] = [
                        'article_id' => (int)$articleId,
                        'locale' => (string)$locale,
                        'date_hour' => $dateHour,
                        'views' => (int)$count,
                    ];
                }
            }
        }

        DB::transaction(function () use ($rows, $viewsLocale) {

            $chunkSize = config('views.chunk_size');

            // -------------------------------
            // 1a. insert/update hourly views
            // -------------------------------
            if (!empty($rows)) {
                foreach (array_chunk($rows, $chunkSize) as $chunk) {
                    DB::table('article_views')->upsert(
                        $chunk,
                        ['article_id', 'locale', 'date_hour'],
                        ['views' => DB::raw('views + VALUES(views)')]
                    );
                }
            }

            // -------------------------------
            // 2. article_translations + articles — all views
            // -------------------------------
            if (!empty($viewsLocale)) {
                /** @noinspection SqlNoDataSourceInspection, SqlResolve */
                DB::statement("
                    CREATE TEMPORARY TABLE tmp_article_views (
                        article_id BIGINT UNSIGNED NOT NULL,
                        locale VARCHAR(10)
                            CHARACTER SET utf8mb4
                            COLLATE utf8mb4_unicode_ci
                            NOT NULL,
                        views BIGINT UNSIGNED NOT NULL,
                        PRIMARY KEY (article_id, locale)
                    ) ENGINE=InnoDB
                ");

                $buffer = [];
                $counter = 0;

                foreach ($viewsLocale as $articleId => $locales) {
                    $articleId = (int)$articleId;

                    foreach ($locales as $locale => $count) {
                        $locale = DB::getPdo()->quote($locale);
                        $count = (int)$count;

                        $buffer[] = "($articleId, $locale, $count)";
                        $counter++;

                        if ($counter >= $chunkSize) {
                            /** @noinspection SqlNoDataSourceInspection, SqlResolve */
                            DB::statement("
                                INSERT INTO tmp_article_views (article_id, locale, views)
                                VALUES " . implode(',', $buffer) . "
                                ON DUPLICATE KEY UPDATE
                                    views = views + VALUES(views)
                            ");
                            $buffer = [];
                            $counter = 0;
                        }
                    }
                }

                if (!empty($buffer)) {
                    /** @noinspection SqlNoDataSourceInspection, SqlResolve */
                    DB::statement("
                        INSERT INTO tmp_article_views (article_id, locale, views)
                        VALUES " . implode(',', $buffer) . "
                        ON DUPLICATE KEY UPDATE
                            views = views + VALUES(views)
                    ");
                }

                // update article_translations — all views
                /** @noinspection SqlNoDataSourceInspection, SqlResolve */
                DB::statement("
                    UPDATE article_translations at
                    JOIN tmp_article_views t
                      ON at.article_id = t.article_id
                     AND at.locale = t.locale
                    SET at.views = at.views + t.views
                ");

                // update articles — all views agregate
                /** @noinspection SqlNoDataSourceInspection, SqlResolve */
                DB::statement("
                    UPDATE articles a
                    JOIN (
                        SELECT article_id, SUM(views) as total_views
                        FROM tmp_article_views
                        GROUP BY article_id
                    ) t ON a.id = t.article_id
                    SET a.views = a.views + t.total_views
                ");

                /** @noinspection SqlNoDataSourceInspection, SqlResolve */
                DB::statement("DROP TEMPORARY TABLE tmp_article_views");
            }
        });
    }


    /**
     * @param array $views
     * @return void
     */
    protected function syncRedisViews(array $views): void
    {
        if (empty($views)) {
            return;
        }

        $key = RedisConst::ARTICLES;

        $missingIds = [];

        foreach ($views as $id => $_) {
            if (!Redis::hexists($key, $id)) {
                $missingIds[] = $id;
                unset($views[$id]);
            }
        }

        if (!empty($missingIds)) {

            $articles = Article::whereIn('id', $missingIds)
                ->pluck('views', 'id');

            foreach ($articles as $id => $viewsCount) {
                Redis::hset($key, $id, $viewsCount);
            }
        }

        foreach ($views as $id => $count) {
            Redis::hincrby($key, $id, $count);
        }
    }


    /**
     * @param Article $article
     * @return array
     */
    public static function getArticleChartData(Article $article): array
    {
        $rows = $article->articleViewsAggregated ?? collect();

        $rows = $rows->keyBy('date_hour');

        $start = $article->created_at->copy()->startOfHour();
        $backDays = config('views.days_after_publication');
        $end = $start->copy()->addDays($backDays);

        $period = CarbonPeriod::create($start, '1 hour', $end);

        $values = [];

        foreach ($period as $dt) {
            $key = $dt->format('Y-m-d H:00:00');
            $values[] = (int) ($rows[$key]->views ?? 0);
        }

        return [
            'labels' => range(0, count($values) - 1),
            'values' => $values,
        ];
    }
}