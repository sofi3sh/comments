<?php

return [
    'menu' => [
        'title' => 'Теги та переклади',
        'tags' => 'Теги',
        'translations' => 'Переклади',
    ],

    'admin' => [
        'title_in_singular' => 'Тег',
        'title_in_plural' => 'Теги',
    ],

    'fields' => [
        'slug' => 'Слаг',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
        'available_locales' => 'Доступні переклади',
        'seo_available_locales' => 'Доступні SEO переклади',
        'name' => 'Назва',
        'title' => 'Заголовок',
        'homepage' => 'Обране',
    ],

    'validation' => [
        'name_required' => 'Поле name обов\'язкове для заповнення.',
        'name_string' => 'Поле name має бути рядком.',
        'name_max' => 'Поле name не може бути довшим за :max символів.',
        'name_unique' => 'Таке name вже використовується.',
    ],
];