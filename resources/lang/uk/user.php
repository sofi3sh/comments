<?php
return [
    'admin'=>[
        'title_in_singular'=>'Користувач',
        'title_in_plural'=>'Користувачі',
    ],

    'fields'=>[
        'name' => 'Ім\'я',
        'surname' => 'Прізвище',
        'email' => 'Email',
        'avatar' => 'Аватар',
        'password' => 'Пароль',
        'password_confirmation' => 'Підтвердження пароля',
        'phone' => 'Номер телефона',
        'facebook_url' => 'Facebook',
        'linkedin_url' => 'LinkedIn',
        'twitter_url' => 'Twitter / X',
        'bio' => 'Про себе',
        'roles' => 'Ролі',
        'role' => 'Роль',
        'status' => 'Статус',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'statuses' => [
        'active' => 'Активний',
        'blocked' => 'Заблокований',
    ],

    'actions' => [
        'block' => 'Заблокувати',
        'unblock' => 'Розблокувати',
    ],

    'messages' => [
        'blocked' => 'Користувача заблоковано.',
        'unblocked' => 'Користувача розблоковано.',
    ],

    'validation'=> [
        'name_required' => 'Ім\'я є обов\'язковим',
        'name_string' => 'Ім\'я повинно бути рядком',
        'name_max' => 'Ім\'я повинно бути не більше 255 символів',
        
        'email_required' => 'Email є обов\'язковим',
        'email_email' => 'Email повинен бути валідним email',
        'email_regex' => 'Email повинен містити @ та домен (наприклад: name@example.com)',
        'email_max' => 'Email повинен бути не більше 255 символів',
        'email_unique' => 'Email вже зареєстрований',

        'phone_required' => 'Номер телефону є обов\'язковим',
        'phone_regex' => 'Дозволені лише цифри, пробіли, + та дефіс',
        'phone_format' => 'Формат українського номера: 0970000000, 380970000000 або +380970000000',
        'phone_max' => 'Номер телефону не більше 20 символів',
        
        'password_required' => 'Пароль є обов\'язковим',
        'password_min' => 'Пароль повинен бути не менше 8 символів',
        'password_confirmed' => 'Паролі не збігаються',
        
        'roles_array' => 'Ролі повинні бути масивом',
        'role_required' => 'Роль є обов\'язковою',
        'role_integer' => 'Роль повинна бути числом',
        'role_exists' => 'Обрана роль не існує',
    ],

];
