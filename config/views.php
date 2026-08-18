<?php

return [

    /*
     * задержка регистрации просмотра (ms)
     */
    'register_delay' => 3000,   // 3 s

    /*
     * TTL unique view
     */
    'view_ttl' => 86400,  // 24 * 60 * 60

    /*
     * daily article views limit for one user
     */
    'daily_limit' => 100, //TODO

    /*
     * show if not less
     */
    'show_limit'  => 10, //TODO

    /*
     * batch limit
     */
    'batch_limit'  => 1000,

    /*
     * chunk size
     */
    'chunk_size'  => 1000,

    /*
     * expire for lock key
     */
    'lock_key_ttl' => 60,

    /*
     * user view cookie ttl
     */
    'view_cookie_ttl' => 60 * 24 * 365,

    /*
     *  cron process
     */
    'process_cron' => env('ARTICLE_VIEWS_PROCESS_CRON', '*/5 * * * *'),

    /*
     * days after article publication
     */
    'days_after_publication' => 7,

    /*
     * hours
     */
    'main_page_refresh_cache_ttl' => 4,

    'articles_with_actions_cache_ttl' => env('ARTICLES_WITH_ACTIONS_CACHE_TTL', 600),
];
