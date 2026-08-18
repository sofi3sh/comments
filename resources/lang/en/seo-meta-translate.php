<?php

return [
    'admin' => [
        'title_in_singular' => 'SEO Meta Translate',
        'title_in_plural' => 'SEO Meta Translates',
    ],
    'fields' => [
        'seo_meta_id' => 'SEO Meta ID',
        'locale' => 'Locale',
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'og_title' => 'OG Title',
        'og_description' => 'OG Description',
        'og_image' => 'OG Image',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'validation' => [
        'seo_meta_required' => 'Please select a SEO meta record.',
        'seo_meta_integer'  => 'The SEO meta ID must be an ID value.',
        'seo_meta_exists'   => 'The selected SEO meta record does not exist.',

        'locale_required' => 'Please choose a locale.',
        'locale_string'   => 'The locale must be a string.',
        'locale_max'      => 'The locale may not be greater than 5 characters.',
        'locale_unique'   => 'This locale already has a translation for the selected record.',

        'meta_title_string'       => 'The meta title must be a string.',
        'meta_title_max'          => 'The meta title may not be greater than 255 characters.',

        'meta_description_string' => 'The meta description must be a string.',
        'meta_description_max'    => 'The meta description may not be greater than 500 characters.',

        'meta_keywords_string' => 'The meta keywords must be a string.',
        'meta_keywords_max'    => 'The meta keywords may not be greater than 500 characters.',

        'og_title_string' => 'The OG title must be a string.',
        'og_title_max'    => 'The OG title may not be greater than 255 characters.',

        'og_description_string' => 'The OG description must be a string.',
        'og_description_max'    => 'The OG description may not be greater than 500 characters.',

        'og_image_string' => 'The OG image path must be a string.',
        'og_image_max'    => 'The OG image path may not be greater than 255 characters.',
    ],
];