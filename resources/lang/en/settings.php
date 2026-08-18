<?php
return [
    'locale' => [
        'title_in_singular' => 'Locale',
        'title_in_plural' => 'Locales',

        'fields' => [
            'name' => 'Name',
            'code' => 'Code',
            'prefix' => 'Prefix URL',
            'icon' => 'Icon',
            'is_default' => 'Is Default',
            'is_active' => 'Status',
            'created_at' => __('admin.date.created_at'),
            'updated_at' => __('admin.date.updated_at'),

            'is_active_yes' => 'Active',
            'is_active_no' => 'Inactive',

            'is_default_yes' => 'Default',
            'is_default_no' => 'Not Default',
        ],

        'validation' => [
            'name_required' => 'Please enter the locale name.',
            'name_string' => 'The locale name must be a text value.',
            'name_max' => 'The locale name may not be greater than 255 characters.',
            'name_unique' => 'The locale name has already been taken.',

            'code_required' => 'Please enter the locale code.',
            'code_string' => 'The locale code must be a text value.',
            'code_max' => 'The locale code may not be greater than 255 characters.',
            'code_unique' => 'The locale code has already been taken.',

            'extension_string' => 'The extension must be a text value.',
            'extension_max' => 'The extension may not be greater than 255 characters.',

            'is_default_boolean' => 'The default flag must be true or false.',

            'is_active_required' => 'The status field is required.',
            'is_active_boolean' => 'The status must be true or false.',
        ],
    ],

    'footer.text_1' => [
        'label' => 'Footer text 1',
        'description' => 'First footer text block'
    ],

    'footer.text_2' => [
        'label' => 'Footer text 2',
        'description' => 'Second footer text block'
    ],

    'footer.copyright' => [
        'label' => 'Copyright',
        'description' => 'Footer copyright text'
    ],

    'contacts.phone' => [
        'label' => 'Phone',
        'description' => 'Main site phone'
    ],

    'contacts.email' => [
        'label' => 'Email',
        'description' => 'Main site email'
    ],

    'social.links' => [
        'label' => 'Social links',
        'description' => 'Site social links'
    ],

    'static-mode' => [
        'label' => 'Static mode',
        'description' => 'Enable or disable site static mode'
    ],

    'groups' => [
        'footer'   => 'Footer',
        'contacts' => 'Contacts',
        'social'   => 'Social links',
        'system'   => 'System',
    ],

    'label' => 'Label',

    'description' => 'Description'
];