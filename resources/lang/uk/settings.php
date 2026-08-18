<?php
return [
    'locale' => [
        'title_in_singular' => 'Локаль',
        'title_in_plural' => 'Локалі',

        'fields' => [
            'name' => 'Назва',
            'code' => 'Код',
            'prefix' => 'Префікс URL',
            'icon' => 'Іконка',
            'is_default' => 'За замовчуванням',
            'is_active' => 'Статус',
            'created_at' => __('admin.date.created_at'),
            'updated_at' => __('admin.date.updated_at'),

            'is_active_yes' => 'Активна',
            'is_active_no' => 'Неактивна',

            'is_default_yes' => 'За замовчуванням',
            'is_default_no' => 'Не є за замовчуванням',
        ],

        'validation' => [
            'name_required' => 'Введіть назву локалі.',
            'name_string' => 'Назва локалі має бути текстом.',
            'name_max' => 'Назва локалі не може перевищувати 255 символів.',
            'name_unique' => 'Така назва локалі вже використовується.',

            'code_required' => 'Введіть код локалі.',
            'code_string' => 'Код локалі має бути текстом.',
            'code_max' => 'Код локалі не може перевищувати 255 символів.',
            'code_unique' => 'Такий код локалі вже використовується.',

            'prefix_string' => 'Префікс має бути текстом.',
            'prefix_max' => 'Префікс не може перевищувати 255 символів.',

            'is_default_boolean' => 'Поле за замовчуванням має бути true або false.',

            'is_active_required' => 'Поле статусу обов\'язкове для заповнення.',
            'is_active_boolean' => 'Статус має бути true або false.',
        ],
    ],

    'footer-text_1' => [
        'label' => 'Текст футера 1',
        'description' => 'Перший текстовий блок у футері'
    ],

    'footer-text_2' => [
        'label' => 'Текст футера 2',
        'description' => 'Другий текстовий блок у футері'
    ],

    'footer-copyright' => [
        'label' => 'Copyright',
        'description' => 'Текст copyright у футері'
    ],

    'contacts-phone' => [
        'label' => 'Телефон',
        'description' => 'Основний телефон сайту'
    ],

    'contacts-email' => [
        'label' => 'Email',
        'description' => 'Основний email сайту'
    ],

    'social-links' => [
        'label' => 'Соціальні мережі',
        'description' => 'Посилання на соціальні мережі сайту'
    ],

    'static-mode' => [
        'label' => 'Статичний режим',
        'description' => 'Увімкнення або вимкнення статичного режиму сайту'
    ],

    'groups' => [
        'footer'   => 'Футер',
        'contacts' => 'Контакти',
        'social'   => 'Соціальні мережі',
        'system'   => 'Система',
    ],

    'label' => 'Назва',

    'description' => 'Опис'
];