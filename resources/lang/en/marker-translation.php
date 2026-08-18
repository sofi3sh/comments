<?php

return [
    'admin' => [
        'title_in_singular' => 'Marker Translation',
        'title_in_plural' => 'Marker Translations',
    ],
    'fields' => [
        'marker_id' => 'Marker',
        'locale' => 'Locale',
        'name' => 'Name',
    ],
    'validation' => [
        'marker_required' => 'The marker field is required.',
        'marker_integer' => 'The marker must be an integer.',
        'marker_exists' => 'The selected marker does not exist.',

        'locale_required' => 'The locale field is required.',
        'locale_string' => 'The locale must be a string.',
        'locale_max' => 'The locale may not be greater than :max characters.',
        'locale_unique' => 'This locale already exists for this marker.',

        'name_required' => 'The name field is required.',
        'name_string' => 'The name must be a string.',
        'name_max' => 'The name may not be greater than :max characters.',
    ],
];

