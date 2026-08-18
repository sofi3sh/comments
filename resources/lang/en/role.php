<?php
return [
    'name' => 'Role name',
    'permissions' => 'Permissions',
    'create_role' => 'Створити роль',
    'edit_role' => 'Редагувати роль',

    'admin'=>[
        'title_in_singular'=>'Role',
        'title_in_plural'=>'Roles',
    ],

    'fields'=>[
        'name'=>'Role name',
        'permissions'=>'Permissions',
        'guard_name'=>'Guard name',
        'rank'=>'Rank',
    ],

    'hints'=>[
        'rank'=>'A lower number means a higher role. If rank is not set, 1000 will be used.',
    ],
];
