<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule as ScheduleTarget;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// php artisan app:process-article-views
// php artisan app:sync-article-content-uniqueness
// php artisan app:process-article-content-uniqueness
// php artisan schedule:work
ScheduleTarget::command('app:process-article-views')
    ->cron(config('views.process_cron', '*/5 * * * *'));

// start every night at 04:33
ScheduleTarget::command('app:process-article-content-uniqueness')
    ->cron(config('article_content.process_cron', '33 4 * * *'));

// php artisan seo:static-warm
// Regenerates missing per-site SEO static files (robots.txt, sitemaps);
// invalidation deletes stale files, this keeps them fresh for crawlers.
ScheduleTarget::command('seo:static-warm')
    ->cron(config('static.seo_warm_cron', '*/15 * * * *'));

// php artisan seo:news-warm
// Rewrites the per-locale Google News sitemaps and the index. Publishing is
// already covered by invalidation; this run drops articles that aged out of
// the news window, which no model event can report.
ScheduleTarget::command('seo:news-warm')
    ->cron(config('static.seo_news_warm_cron', '0 * * * *'));
