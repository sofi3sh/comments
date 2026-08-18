<?php

return [
    'admin' => [
        'title_in_singular' => 'SEO мета',
        'title_in_plural' => 'SEO мета дані',
    ],
    'fields' => [
        'entity_type' => 'Тип сутності',
        'entity_id' => 'ID сутності',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'validation' => [
        'entity_type_required' => 'Укажите тип сутности.',
        'entity_type_string'   => 'Тип сутности должен быть строкой.',
        'entity_type_max'      => 'Тип сутности не может быть длиннее 50 символов.',

        'entity_id_required' => 'Укажите ID сутности.',
        'entity_id_integer'  => 'ID сутности должен быть числом.',
        'entity_id_min'      => 'ID сутности должен быть не меньше 1.',
    ],
];