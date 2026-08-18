<?php
return [
    'locale' => [
        'title_in_singular' => 'Локаль',
        'title_in_plural' => 'Локали',

        'fields' => [
            'name' => 'Название',
            'code' => 'Код',
            'prefix' => 'Префикс URL',
            'icon' => 'Иконка',
            'is_default' => 'По умолчанию',
            'is_active' => 'Статус',
            'created_at' => __('admin.date.created_at'),
            'updated_at' => __('admin.date.updated_at'),

            'is_active_yes' => 'Активна',
            'is_active_no' => 'Неактивна',

            'is_default_yes' => 'По умолчанию',
            'is_default_no' => 'Не является по умолчанию',
        ],

        'validation' => [
            'name_required' => 'Введите название локали.',
            'name_string' => 'Название локали должно быть текстом.',
            'name_max' => 'Название локали не может быть длиннее 255 символов.',
            'name_unique' => 'Такое название локали уже используется.',

            'code_required' => 'Введите код локали.',
            'code_string' => 'Код локали должен быть текстом.',
            'code_max' => 'Код локали не может быть длиннее 255 символов.',
            'code_unique' => 'Такой код локали уже используется.',

            'prefix_string' => 'Префикс должен быть текстом.',
            'prefix_max' => 'Префикс не может быть длиннее 255 символов.',

            'is_default_boolean' => 'Поле по умолчанию должно быть true или false.',

            'is_active_required' => 'Поле статуса обязательно для заполнения.',
            'is_active_boolean' => 'Статус должен быть true или false.',
        ],
    ],

    'footer.text_1' => [
        'label' => 'Текст футера 1',
        'description' => 'Первый текстовый блок в футере'
    ],

    'footer.text_2' => [
        'label' => 'Текст футера 2',
        'description' => 'Второй текстовый блок в футере'
    ],

    'footer.copyright' => [
        'label' => 'Copyright',
        'description' => 'Текст copyright в футере'
    ],

    'contacts.phone' => [
        'label' => 'Телефон',
        'description' => 'Основной телефон сайта'
    ],

    'contacts.email' => [
        'label' => 'Email',
        'description' => 'Основной email сайта'
    ],

    'social.links' => [
        'label' => 'Социальные сети',
        'description' => 'Ссылки на социальные сети сайта'
    ],

    'static-mode' => [
        'label' => 'Статический режим',
        'description' => 'Включение или выключение статического режима сайта'
    ],

    'groups' => [
        'footer'   => 'Footer',
        'contacts' => 'Contacts',
        'social'   => 'Social links',
        'system'   => 'Система',
    ],

    'label' => 'Название',

    'description' => 'Описание'
];