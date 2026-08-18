<?php

return [
    'admin' => [
        'title_in_singular' => 'Переклад статті',
        'title_in_plural' => 'Переклади статей',
    ],

    'fields' => [
        'article_id' => 'Стаття',
        'locale' => 'Мова',
        'title' => 'Заголовок',
        'excerpt' => 'Анотація',
        'content' => 'Контент',
        'slug' => 'Slug',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'validation' => [
        'article_required' => 'Будь ласка, виберіть статтю.',
        'article_integer'  => 'Поле статті має містити ID.',
        'article_exists'   => 'Обрана стаття не існує.',

        'locale_required' => 'Будь ласка, виберіть мову.',
        'locale_string'   => 'Мова має бути рядком.',
        'locale_max'      => 'Мова не може містити більше 5 символів.',
        'locale_unique'   => 'Для цієї статті переклад цією мовою вже існує.',

        'title_required' => 'Поле заголовка обовʼязкове.',
        'title_string'   => 'Заголовок має бути рядком.',
        'title_max'      => 'Заголовок не може бути довшим за 255 символів.',

        'excerpt_string' => 'Анотація має бути рядком.',
        'content_string' => 'Контент має бути рядком.',

        'slug_string' => 'Slug має бути рядком.',
        'slug_max'    => 'Slug не може бути довшим за 255 символів.',
    ],

    'auto_translate' => [
        'source_locale' => 'Исходный язык',
        'target_locale' => 'Целевой язык',
        'source' => 'Исходный перевод',
        'target' => 'Целевой перевод',
        'overwrite' => 'Перезаписать заполненные поля',
        'overwrite_confirm' => 'Перезаписать перевод?',
        'button' => 'Автоперевести',
        'loading' => 'Перевод...',
        'success' => 'Перевод заполнен.',
        'skipped' => 'Пропущено',
        'config_error' => 'Автоперевод не настроен.',
        'same_locale' => 'Выберите разные языки.',
        'content_too_large' => 'Контент слишком большой для синхронного перевода. Лимит: :limit символов.',
    ],
];
