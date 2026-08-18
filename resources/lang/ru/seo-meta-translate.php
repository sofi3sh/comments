<?php

return [
    'admin' => [
        'title_in_singular' => 'Перевод SEO-меты',
        'title_in_plural' => 'Переводы SEO-меты',
    ],
    'fields' => [
        'seo_meta_id' => 'SEO Meta',
        'locale' => 'Локаль',
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
        'seo_meta_required' => 'Пожалуйста, выберите запись SEO-меты.',
        'seo_meta_integer'  => 'Поле SEO-меты должно быть числовым ID.',
        'seo_meta_exists'   => 'Выбранная запись SEO-меты не существует.',

        'locale_required' => 'Пожалуйста, выберите локаль.',
        'locale_string'   => 'Локаль должна быть строкой.',
        'locale_max'      => 'Локаль не может быть длиннее 5 символов.',
        'locale_unique'   => 'Для этой SEO-меты уже есть перевод на выбранной локали.',

        'meta_title_string'       => 'Поле Meta Title должно быть строкой.',
        'meta_title_max'          => 'Поле Meta Title не может быть длиннее 255 символов.',

        'meta_description_string' => 'Поле Meta Description должно быть строкой.',
        'meta_description_max'    => 'Поле Meta Description не может быть длиннее 500 символов.',

        'meta_keywords_string' => 'Поле Meta Keywords должно быть строкой.',
        'meta_keywords_max'    => 'Поле Meta Keywords не может быть длиннее 500 символов.',

        'og_title_string' => 'Поле OG Title должно быть строкой.',
        'og_title_max'    => 'Поле OG Title не может быть длиннее 255 символов.',

        'og_description_string' => 'Поле OG Description должно быть строкой.',
        'og_description_max'    => 'Поле OG Description не может быть длиннее 500 символов.',

        'og_image_string' => 'Поле OG Image должно быть строкой.',
        'og_image_max'    => 'Поле OG Image не может быть длиннее 255 символов.',
    ],
];