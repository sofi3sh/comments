<?php

return  [

    'twitter_creator' => '@CommentsUA',

    'twitter_site' => '@CommentsUA',

    'theme_color' => '#D01E45',

    'default_og_image' => 'images/default_1200_630.webp',

    /*
    |--------------------------------------------------------------------------
    | Google News sitemaps
    |--------------------------------------------------------------------------
    |
    | One sitemap per locale is published at /sitemaps/news_{locale}.xml and
    | listed in the sitemap index. Google reads them as a rolling window of
    | recent articles and ignores anything older than about two days.
    |
    */

    'news' => [

        // How far back an article may have been published to still be listed.
        'window_hours' => (int) env('SEO_NEWS_WINDOW_HOURS', 48),

        // Hard limit of the Google News sitemap format.
        'max_urls' => 1000,

        // ArticleType codes that belong in a news sitemap. Press releases,
        // infographics and the person/company dossier pages are not news.
        'types' => [
            'news',
            'article',
            'interview',
            'opinion',
        ],

        // Optional per-locale override of <news:publication><news:name>, for
        // when a language edition is registered under its own publication
        // name. Falls back to the site name.
        // e.g. ['uk' => 'Коментарі', 'ru' => 'Комментарии']
        'publication_name' => [],

    ],

];
