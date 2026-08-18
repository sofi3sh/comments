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
        'article_required' => 'Будь ласка, оберіть статтю.',
        'article_integer'  => 'Поле статті має містити ID.',
        'article_exists'   => 'Обрана стаття не існує.',

        'locale_required' => 'Будь ласка, оберіть мову.',
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
        'source_locale' => 'Мова оригіналу',
        'target_locale' => 'Цільова мова',
        'source' => 'Вихідний переклад',
        'target' => 'Цільовий переклад',
        'overwrite' => 'Перезаписати заповнені поля',
        'overwrite_confirm' => 'Перезаписати переклад?',
        'button' => 'Автоперекласти',
        'loading' => 'Переклад...',
        'success' => 'Переклад заповнено.',
        'skipped' => 'Пропущено',
        'config_error' => 'Автопереклад не налаштовано.',
        'same_locale' => 'Оберіть різні мови.',
        'content_too_large' => 'Контент завеликий для синхронного перекладу. Ліміт: :limit символів.',
    ],
];
