<?php

return [
    'admin' => [
        'title_in_singular' => 'Article Type',
        'title_in_plural' => 'Article Types',
    ],
    'fields' => [
        'code' => 'Code',
        'name' => 'Name',
        'is_active' => 'Active',
        'is_splittable' => 'Split content',
        'position' => 'Position',
    ],
    'hints' => [
        'code' => 'Unique code (e.g., "news", "interview"). Only lowercase letters, numbers, and underscores allowed.',
        'is_splittable' => 'When enabled, the static page has public HTML and private REST content.',
    ],
];
