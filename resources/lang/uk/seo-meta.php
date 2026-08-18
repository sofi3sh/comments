<?php

return [
    'admin' => [
        'title_in_singular' => 'SEO мета',
        'title_in_plural' => 'SEO метадані',
    ],
    'fields' => [
        'entity_type' => 'Тип сутності',
        'entity_id' => 'ID сутності',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'validation' => [
        'entity_type_required' => 'Вкажіть тип сутності.',
        'entity_type_string'   => 'Тип сутності має бути рядком.',
        'entity_type_max'      => 'Тип сутності не може бути довшим за 50 символів.',

        'entity_id_required' => 'Вкажіть ID сутності.',
        'entity_id_integer'  => 'ID сутності має бути числом.',
        'entity_id_min'      => 'ID сутності має бути не меншим за 1.',
    ],
];