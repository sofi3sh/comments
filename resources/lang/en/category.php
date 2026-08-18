<?php
return [
    'admin' => [
        'title_in_singular' => 'Category',
        'title_in_plural'   => 'Categories',
    ],
  
    'fields' => [
        'slug'       => 'Slug',
        'homepage'   => 'Homepage',
        'subdomain'  => 'Subdomain',
        'site_id'    => 'Site',
        'parent_id'  => 'Parent Category',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
        'available_locales' => 'Available Locales',
        'seo_available_locales' => 'SEO Available Locales',
    ],
  
    'validation' => [
        'site_required' => 'Please select a site.',
        'site_integer'  => 'The site field must be an ID value.',
        'site_exists'   => 'The selected site does not exist.',

        'parent_integer' => 'The parent category field must be an ID value.',
        'parent_exists'  => 'The selected parent category does not exist.',
        'parent_not_in'  => 'A category cannot be its own parent.',

        'slug_required' => 'The slug field is required.',
        'slug_string'   => 'The slug field must be a string.',
        'slug_max'      => 'The slug field may not be greater than 255 characters.',
        'slug_unique'   => 'The slug has already been taken.',
    ],

    'translations' => [
        'name' => 'Name',
        'description' => 'Description',
        'slug' => 'Slug',
    ],

    'footer' => [
        'categories' => 'Categories',
        'politics' => 'Politics',
        'business' => 'Business',
        'world' => 'World',
        'society' => 'Society',
        'health' => 'Health',
        'leisure' => 'Leisure',
        'hi_tech' => 'Hi-tech',
        'sports' => 'Sports',
        'celebrities' => 'Celebrities',
        'men' => 'Men',
        'dossiers' => 'Dossiers',

        'regions' => 'Regions',
        'kharkiv' => 'Kharkiv',
        'kyiv' => 'Kyiv',
        'odesa' => 'Odesa',
        'dnipro' => 'Dnipro',
        'donbas' => 'Donbas',

        'sections' => 'Sections',
        'news' => 'News',
        'press_releases' => 'Press releases',
        'infographics' => 'Infographics',
        'articles' => 'Articles',
        'opinions' => 'Opinions',
        'blog' => 'Blog',

        'information' => 'Information',
        'about_the_project' => 'About the project',
        'advertising' => 'Advertising',
        'bloggers' => 'Bloggers',
        'news_archive' => 'News archive',

        'markets' => 'Markets',
        'pre_markets' => 'Pre-markets',
        'after_hours' => 'After-Hours',
        'fear_&_greed' => 'Fear & Greed',
        'investing' => 'Investing',
        'markets_now' => 'Markets Now',
        'nightcap' => 'Nightcap',

        'life_but_better' => 'Life, But Better',
        'fitness' => 'Fitness',
        'food' => 'Food',
        'sleep' => 'Sleep',
        'mindfulness' => 'Mindfulness',
    ]
];