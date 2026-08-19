<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_news' => [
        'comments_url' => env('GOOGLE_NEWS_COMMENTS_URL', 'https://news.google.com/publications/CAAiEJyqHZB4rffCKRtEBRWUCeMqFAgKIhCcqh2QeK33wikbRAUVlAnj?hl=ru&gl=UA&ceid=UA%3Aru'),
    ],

    'youtube' => [
        'short_hosts' => [
            'youtu.be',
            'www.youtu.be',
        ],
        'hosts' => [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'youtube-nocookie.com',
            'www.youtube-nocookie.com',
        ],
        'watch_url' => 'https://www.youtube.com/watch?v=%s',
        'embed_url' => 'https://www.youtube-nocookie.com/embed/%s?autoplay=1',
        'thumbnail_url' => 'https://i.ytimg.com/vi/%s/maxresdefault.jpg',
        'thumbnail_fallback_url' => 'https://i.ytimg.com/vi/%s/hqdefault.jpg',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'gtm' => [
        'id' => env('GTM_ID'),
    ],

];
