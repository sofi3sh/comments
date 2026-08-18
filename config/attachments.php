<?php

return [

    'image' => [

        'extensions' => [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'gif',
        ],

        'mimetypes' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
        ],

        'max_size_kb' => 10240,

        'max_width' => 6000,

        'max_height' => 6000,

        'sizes' => [
            'card_sm' => [
                'width' => 320,
                'height' => 180,
                'mode' => 'cover',
            ],
            'card_lg' => [
                'width' => 640,
                'height' => 360,
                'mode' => 'cover',
            ],
            'cover' => [
                'width' => 1280,
                'height' => null,
                'mode' => 'contain',
            ],
        ],
    ],
];
