<?php
return [
    'admin'=>[
        'title_in_singular'=>'Пользователь',
        'title_in_plural'=>'Пользователи',
    ],


    'fields'=>[
        'name' => 'Имя',
        'surname' => 'Фамилия',
        'email' => 'Email',
        'avatar' => 'Аватар',
        'password' => 'Пароль',
        'password_confirmation' => 'Подтверждение пароля',
        'phone' => 'Номер телефона',
        'facebook_url' => 'Facebook',
        'linkedin_url' => 'LinkedIn',
        'twitter_url' => 'Twitter / X',
        'bio' => 'О себе',
        'roles' => 'Роли',
        'role' => 'Роль',
        'status' => 'Статус',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'statuses' => [
        'active' => 'Активен',
        'blocked' => 'Заблокирован',
    ],

    'actions' => [
        'block' => 'Заблокировать',
        'unblock' => 'Разблокировать',
    ],

    'messages' => [
        'blocked' => 'Пользователь заблокирован.',
        'unblocked' => 'Пользователь разблокирован.',
    ],

    'validation'=> [
        'name_required' => 'Имя является обязательным',
        'name_string' => 'Имя должно быть строкой',
        'name_max' => 'Имя должно быть не более 255 символов',
        
        'email_required' => 'Email является обязательным',
        'email_email' => 'Email должен быть валидным email',
        'email_regex' => 'Email должен содержать @ и домен (например: name@example.com)',
        'email_max' => 'Email должен быть не более 255 символов',
        'email_unique' => 'Email уже зарегистрирован',

        'phone_required' => 'Номер телефона является обязательным',
        'phone_regex' => 'Допускаются только цифры, пробелы, + и дефис',
        'phone_format' => 'Формат украинского номера: 0970000000, 380970000000 или +380970000000',
        'phone_max' => 'Номер телефона не более 20 символов',
        
        'password_required' => 'Пароль является обязательным',
        'password_min' => 'Пароль должен быть не менее 8 символов',
        'password_confirmed' => 'Пароли не совпадают',
        
        'roles_array' => 'Роли должны быть массивом',
        'role_required' => 'Роль является обязательной',
        'role_integer' => 'Роль должна быть числом',
        'role_exists' => 'Выбранная роль не существует',
    ],

];
