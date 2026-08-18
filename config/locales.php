<?php

return [

    'available' => [
        'en' => 'English',
        'ru' => 'Русский',
        'uk' => 'Українська',
    ],
    
    'default' => env('APP_LOCALE', 'en'),
    
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
];