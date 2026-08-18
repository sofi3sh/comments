<?php
return [
    'admin' => [
        'title_in_singular' => 'Категория',
        'title_in_plural'   => 'Категории',
    ],

    'fields' => [
        'slug'       => 'Слаг',
        'homepage'   => 'Домашняя страница',
        'subdomain'  => 'Поддомен',
        'site_id'    => 'Сайт',
        'parent_id'  => 'Родительская категория',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
        'available_locales' => 'Доступные локалі',
        'seo_available_locales' => 'Доступные SEO локалі',
    ],

    'validation' => [
        'site_required' => 'Выберите сайт.',
        'site_integer'  => 'Поле сайта должно быть числовым ID.',
        'site_exists'   => 'Выбранный сайт не найден.',

        'parent_integer' => 'Поле родительской категории должно быть числовым ID.',
        'parent_exists'  => 'Выбранная родительская категория не найдена.',
        'parent_not_in'  => 'Категория не может быть родителем сама себе.',

        'slug_required' => 'Поле слага обязательное.',
        'slug_string'   => 'Слаг должен быть строкой.',
        'slug_max'      => 'Слаг не может быть длиннее 255 символов.',
        'slug_unique'   => 'Такой слаг уже используется.',
    ],

    'translations' => [
        'name' => 'Название',
        'description' => 'Описание',
        'slug' => 'Слаг',
    ],

    //todo
    'footer' => [
        'categories' => 'Категории',
        'politics' => 'Политика',
        'business' => 'Бизнес',
        'world' => 'Мир',
        'society' => 'Общество',
        'health' => 'Здоровье',
        'leisure' => 'Досуг',
        'hi_tech' => 'Hi-tech',
        'sports' => 'Спорт',
        'celebrities' => "Звёзды",
        'men' => 'Человек',
        'dossiers' => 'Досье',

        'regions' => 'Регионы',
        'kharkiv' => 'Харьков',
        'kyiv' => 'Киев',
        'odesa' => 'Одесса',
        'dnipro' => 'Днепр',
        'donbas' => 'Донбас',

        'sections' => 'Разделы',
        'news' => 'Новости',
        'press_releases' => 'Пресс релизы',
        'infographics' => 'Инфорграфика',
        'articles' => 'Статьи',
        'opinions' => 'Мнения',
        'blog' => 'Блог',

        'information' => 'Информация',
        'about_the_project' => 'О проекте',
        'advertising' => 'Реклама',
        'bloggers' => 'Блогеры',
        'news_archive' => 'Архив новостей',

        'markets' => 'Markets',
        'pre_markets' => 'Pre-markets',
        'after_hours' => 'After-Hours',
        'fear_&_greed' => 'Fear & Greed',
        'investing' => 'Инвестиции',
        'markets_now' => 'Markets Now',
        'nightcap' => 'Nightcap',

        'life_but_better' => 'Life, But Better',
        'fitness' => 'Фитнес',
        'food' => 'Питание',
        'sleep' => 'Отдых',
        'mindfulness' => 'Mindfulness',
    ]
];
