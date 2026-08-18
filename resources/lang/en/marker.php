<?php

return [
    'admin' => [
        'title_in_singular' => 'Marker',
        'title_in_plural' => 'Markers',
    ],
    'fields' => [
        'code' => 'Technical code',
        'icon' => 'Icon',
        'is_active' => 'Active',
        'is_system' => 'System',
        'available_locales' => 'Available locales',
    ],
    'hints' => [
        'icon' => 'Enter icon class (e.g., "bi bi-star") or SVG/HTML code',
    ],
    'errors' => [
        'system_delete_forbidden' => 'The system marker cannot be deleted.',
        'system_properties_immutable' => 'The technical properties of a system marker cannot be changed.',
    ],
];
