<?php
return [
    'admin' => [
        'title_in_singular' => 'Заявка пользователя',
        'title_in_plural' => 'Заявки пользователей',
    ],

    'fields' => [
        'application_type' => 'Тип заявки',
        'company_name' => 'Название компании',
        'types' => [
            'blogger' => 'Блогер',
            'company_representative' => 'Представитель компании',
        ],
        'company_data' => 'Данные компании',
    ],

    'actions' => [
        'approve' => 'Подтвердить',
        'reject' => 'Отклонить',
        'confirm_approve' => 'Подтвердить эту заявку?',
        'confirm_reject' => 'Отклонить эту заявку?',
    ],

    'messages' => [
        'approve_blogger_success' => 'Заявка блогера успешно подтверждена.',
        'approve_company_success' => 'Заявка представителя компании успешно подтверждена.',
        'reject_success' => 'Заявка пользователя успешно отклонена.',
    ],
];

