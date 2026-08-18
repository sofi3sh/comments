<?php

return [
    'admin' => [
        'title_in_singular' => 'Маркер',
        'title_in_plural' => 'Маркеры',
    ],
    'fields' => [
        'code' => 'Технический код',
        'icon' => 'Иконка',
        'is_active' => 'Активен',
        'is_system' => 'Системный',
        'available_locales' => 'Доступные переводы',
    ],
    'hints' => [
        'icon' => 'Введите класс иконки (например, "bi bi-star") или SVG/HTML код',
    ],
    'errors' => [
        'system_delete_forbidden' => 'Системный маркер нельзя удалить.',
        'system_properties_immutable' => 'Невозможно изменить технические свойства системного маркера.',
    ],
];
