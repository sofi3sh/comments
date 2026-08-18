<?php

return [

    'provider' => env('ARTICLE_TRANSLATION_PROVIDER', 'google'),

    'max_sync_characters' => (int) env('ARTICLE_TRANSLATION_MAX_SYNC_CHARACTERS', 30000),

    'providers' => [

        'google' => [

            'key' => env('GOOGLE_TRANSLATE_API_KEY'),

            'endpoint' => env(
                'GOOGLE_TRANSLATE_ENDPOINT',
                'https://translation.googleapis.com/language/translate/v2'
            ),
        ],
    ],
];
