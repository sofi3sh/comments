<?php

return [
    'admin' => [
        'title_in_singular' => 'SEO Meta',
        'title_in_plural' => 'SEO Metas',
    ],
    'fields' => [
        'entity_type' => 'Entity type',
        'entity_id' => 'Entity ID',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'validation' => [
        'entity_type_required' => 'Please specify the entity type.',
        'entity_type_string'   => 'The entity type must be a string.',
        'entity_type_max'      => 'The entity type may not be greater than 50 characters.',

        'entity_id_required' => 'Please provide the entity ID.',
        'entity_id_integer'  => 'The entity ID must be a numeric value.',
        'entity_id_min'      => 'The entity ID must be at least 1.',
    ],
];