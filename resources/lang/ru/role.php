<?php

return [
    'name' => 'Название роли',
    'permissions' => 'Права',
    'create_role' => 'Создать роль',
    'edit_role' => 'Редактировать роль',

    'admin'=>[
        'title_in_singular'=>'Роль',
        'title_in_plural'=>'Роли',
    ],

    'fields'=>[
        'name'=>'Название роли',
        'permissions'=>'Разрешения',
        'guard_name'=>'Guard name',
        'rank'=>'Ранг',
    ],

    'hints'=>[
        'rank'=>'Меньшее число означает более высокую роль. Если ранг не задан, будет использовано 1000.',
    ],
];
