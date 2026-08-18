<?php

return [

    'enabled_locales' => array_filter(
        array_map('trim', explode(',', env('ARTICLE_CONTENT_UNIQUENESS_LOCALES', 'uk,ru')))
    ),

    'batch_size' => (int) env('ARTICLE_CONTENT_UNIQUENESS_BATCH_SIZE', 5),

    'process_cron' => env('ARTICLE_CONTENT_UNIQUENESS_CRON', '33 4 * * *'),

    'providers' => [

        'content_watch' => [

            'key' => env('CONTENT_WATCH_API_KEY'),

            'endpoint' => env(
                'CONTENT_WATCH_ENDPOINT',
                'https://content-watch.ru/public/api/'
            ),
        ],
    ],
];
