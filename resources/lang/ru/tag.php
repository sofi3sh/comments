<?php

return [
    'menu' => [
        'title' => 'Теги и переводы',
        'tags' => 'Теги',
        'translations' => 'Переводы',
    ],

    'admin' => [
        'title_in_singular' => 'Тег',
        'title_in_plural' => 'Теги',
    ],

    'fields' => [
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
        'available_locales' => 'Доступные переводы',
        'seo_available_locales' => 'Доступные SEO переводы',
        'name' => 'Название',
        'slug' => 'Слаг',
        'title' => 'Заголовок',
        'homepage' => 'Избранное',
    ],
    
    'validation' => [
        'name_required' => 'Поле name обязательно для заполнения.',
        'name_string' => 'Поле name должно быть строкой.',
        'name_max' => 'Поле name не может быть длиннее :max символов.',
        'name_unique' => 'Такое name уже используется.',
    ],
];