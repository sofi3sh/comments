<?php

return [
    'name' => 'Назва ролі',
    'permissions' => 'Права',
    'create_role' => 'Створити роль',
    'edit_role' => 'Редагувати роль',

    'admin'=>[
        'title_in_singular'=>'Роль',
        'title_in_plural'=>'Ролі',
    ],

    'fields'=>[
        'name'=>'Назва ролі',
        'permissions'=>'Дозволи',
        'guard_name'=>'Guard name',
        'rank'=>'Ранг',
    ],

    'hints'=>[
        'rank'=>'Менше число означає вищу роль. Якщо ранг не задано, буде використано 1000.',
    ],
];
