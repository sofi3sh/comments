<?php
return [
    'admin' => [
        'title_in_singular' => 'Категорія',
        'title_in_plural'   => 'Категорії',
    ],

    'fields' => [
        'slug'       => 'Слаг',
        'homepage'   => 'Домашня сторінка',
        'subdomain'  => 'Піддомен',
        'site_id'    => 'Сайт',
        'parent_id'  => 'Батьківська категорія',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
        'available_locales' => 'Доступні локалі',
        'seo_available_locales' => 'Доступні SEO локалі',
    ],

    'validation' => [
        'site_required' => 'Оберіть сайт.',
        'site_integer'  => 'Поле сайту має бути числовим ID.',
        'site_exists'   => 'Обраний сайт не знайдено.',

        'parent_integer' => 'Поле батьківської категорії має бути числовим ID.',
        'parent_exists'  => 'Обрану батьківську категорію не знайдено.',
        'parent_not_in'  => 'Категорія не може бути батьком сама собі.',

        'slug_required' => 'Поле слаг є обовʼязковим.',
        'slug_string'   => 'Слаг має бути рядком.',
        'slug_max'      => 'Слаг не може бути довшим за 255 символів.',
        'slug_unique'   => 'Такий слаг уже використовується.',
    ],

    'translations' => [
        'name' => 'Назва',
        'description' => 'Опис',
        'slug' => 'Слаг',
    ],

    //todo
    'footer' => [
        'categories' => 'Категорії',
        'politics' => 'Політика',
        'business' => 'Бізнес',
        'world' => 'Світ',
        'society' => 'Суспільство',
        'health' => 'Здоров\'я',
        'leisure' => 'Дозвілля',
        'hi_tech' => 'Hi-tech',
        'sports' => 'Спорт',
        'celebrities' => 'Зірки',
        'men' => 'Людина',
        'dossiers' => 'Дос\'є',

        'regions' => 'Регіони',
        'kharkiv' => 'Харків',
        'kyiv' => 'Київ',
        'odesa' => 'Одеса',
        'dnipro' => 'Дніпро',
        'donbas' => 'Донбас',

        'sections' => 'Розділи',
        'news' => 'Новини',
        'press_releases' => 'Прес релізи',
        'infographics' => 'Інфографіка',
        'articles' => 'Статті',
        'opinions' => 'Думки',
        'blog' => 'Блог',

        'information' => 'Інформація',
        'about_the_project' => 'Про проект',
        'advertising' => 'Реклама',
        'bloggers' => 'Блогери',
        'news_archive' => 'Архів новин',

        'markets' => 'Markets',
        'pre_markets' => 'Pre-markets',
        'after_hours' => 'After-Hours',
        'fear_&_greed' => 'Fear & Greed',
        'investing' => 'Інвестиції',
        'markets_now' => 'Markets Now',
        'nightcap' => 'Nightcap',

        'life_but_better' => 'Life, But Better',
        'fitness' => 'Фітнес',
        'food' => 'Їжа',
        'sleep' => 'Відпочинок',
        'mindfulness' => 'Mindfulness',
    ]
];
