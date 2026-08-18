<?php

return [
    'manual_invalidation' => [
        'delete_batch_size' => (int) env('STATIC_MANUAL_INVALIDATION_DELETE_BATCH_SIZE', 1000),
    ],

    // Cron for the seo:static-warm scheduled run.
    'seo_warm_cron' => env('STATIC_SEO_WARM_CRON', '*/15 * * * *'),

    // Cron for the seo:news-warm scheduled run. Article changes already drop
    // the news sitemaps through SeoStaticInvalidator; this run only exists to
    // expire entries that aged out of the news window without being edited.
    'seo_news_warm_cron' => env('STATIC_SEO_NEWS_WARM_CRON', '0 * * * *'),
];
