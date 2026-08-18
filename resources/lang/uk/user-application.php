<?php
return [
    'admin' => [
        'title_in_singular' => 'Заявка користувача',
        'title_in_plural' => 'Заявки користувачів',
    ],

    'fields' => [
        'application_type' => 'Тип заявки',
        'company_name' => 'Назва компанії',
        'types' => [
            'blogger' => 'Блогер',
            'company_representative' => 'Представник компанії',
        ],
        'company_data' => 'Дані компанії',
    ],

    'actions' => [
        'approve' => 'Підтвердити',
        'reject' => 'Відхилити',
        'confirm_approve' => 'Підтвердити цю заявку?',
        'confirm_reject' => 'Відхилити цю заявку?',
    ],

    'messages' => [
        'approve_blogger_success' => 'Заявку блогера успішно підтверджено.',
        'approve_company_success' => 'Заявку представника компанії успішно підтверджено.',
        'reject_success' => 'Заявку користувача успішно відхилено.',
    ],
];

