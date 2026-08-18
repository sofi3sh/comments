<?php

return [
    'admin' => [
        'title_in_singular' => 'Маркер',
        'title_in_plural' => 'Маркери',
    ],
    'fields' => [
        'code' => 'Технічний код',
        'icon' => 'Іконка',
        'is_active' => 'Активний',
        'is_system' => 'Системний',
        'available_locales' => 'Доступні переклади',
    ],
    'hints' => [
        'icon' => 'Введіть клас іконки (наприклад, "bi bi-star") або SVG/HTML код',
    ],
    'errors' => [
        'system_delete_forbidden' => 'Системний маркер не можна видалити.',
        'system_properties_immutable' => 'Неможливо змінити технічні властивості системного маркера.',
    ],
];
