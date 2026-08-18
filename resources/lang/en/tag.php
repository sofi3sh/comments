<?php

return [
    'menu' => [
        'title' => 'Tags and translations',
        'tags' => 'Tags',
        'translations' => 'Translations',
    ],

    'admin' => [
        'title_in_singular' => 'Tag',
        'title_in_plural' => 'Tags',
    ],

    'fields' => [
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
        'available_locales' => 'Available locales',
        'seo_available_locales' => 'Available SEO locales',
        'name' => 'Name',
        'slug' => 'Slug',
        'title' => 'Title',
        'homepage' => 'Featured',
    ],

    'validation' => [
        'name_required' => 'The name field is required.',
        'name_string' => 'The name must be a string.',
        'name_max' => 'The name cannot be longer than 255 characters.',
        'name_unique' => 'The name has already been taken.',
    ],

];