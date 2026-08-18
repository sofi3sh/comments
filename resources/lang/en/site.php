<?php
return [
    'admin' => [
        'title_in_singular' => 'Site',
        'title_in_plural' => 'Sites',
    ],

    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'domain' => 'Domain',
        'color_primary' => 'Primary Color',
        'color_secondary' => 'Secondary Color',
        'logo' => 'Logo',
        'active' => 'Active',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'validation' => [
        'name_required'  => 'Please enter the site name.',
        'name_string'    => 'The site name must be a text value.',
        'name_max'       => 'The site name may not be greater than 255 characters.',

        'slug_required'  => 'Please enter the slug.',
        'slug_string'    => 'The slug must be a text value.',
        'slug_max'       => 'The slug may not be greater than 100 characters.',
        'slug_unique'    => 'The slug has already been taken.',

        'domain_required' => 'Please enter the domain.',
        'domain_string'   => 'The domain must be a text value.',
        'domain_max'      => 'The domain may not be greater than 255 characters.',
        'domain_unique'   => 'The domain has already been taken.',

        'color_primary_string'   => 'The primary color must be a text value.',
        'color_primary_max'      => 'The primary color may not be greater than 20 characters.',
        'color_secondary_string' => 'The secondary color must be a text value.',
        'color_secondary_max'    => 'The secondary color may not be greater than 20 characters.',

        'logo_string' => 'The logo path must be a text value.',
        'logo_max'    => 'The logo path may not be greater than 255 characters.',

        'active_boolean' => 'The active flag must be true or false.',
    ],

    
];