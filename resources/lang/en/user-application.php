<?php
return [
    'admin' => [
        'title_in_singular' => 'User Application',
        'title_in_plural' => 'User Applications',
    ],

    'fields' => [
        'application_type' => 'Application type',
        'company_name' => 'Company name',
        'types' => [
            'blogger' => 'Blogger',
            'company_representative' => 'Company representative',
        ],
        'company_data' => 'Company data',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'confirm_approve' => 'Approve this application?',
        'confirm_reject' => 'Reject this application?',
    ],

    'messages' => [
        'approve_blogger_success' => 'Blogger application has been approved.',
        'approve_company_success' => 'Company representative application has been approved.',
        'reject_success' => 'User application has been rejected.',
    ],
];

