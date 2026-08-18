<?php

return [
    'admin' => [
        'title_in_singular' => 'Переклад SEO-мети',
        'title_in_plural' => 'Переклади SEO-мети',
    ],
    'fields' => [
        'seo_meta_id' => 'SEO Мета',
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
        'seo_meta_required' => 'Будь ласка, виберіть SEO-мету.',
        'seo_meta_integer'  => 'Поле SEO-мети має бути ID.',
        'seo_meta_exists'   => 'Обраної SEO-мети не існує.',

        'locale_required' => 'Будь ласка, оберіть локаль.',
        'locale_string'   => 'Локаль має бути рядком.',
        'locale_max'      => 'Локаль не може бути довшою за 5 символів.',
        'locale_unique'   => 'Для цієї SEO-мети вже є переклад на вибраній локалі.',

        'meta_title_string'       => 'Поле Meta Title має бути рядком.',
        'meta_title_max'          => 'Поле Meta Title не може бути довшим за 255 символів.',

        'meta_description_string' => 'Поле Meta Description має бути рядком.',
        'meta_description_max'    => 'Поле Meta Description не може бути довшим за 500 символів.',

        'meta_keywords_string' => 'Поле Meta Keywords має бути рядком.',
        'meta_keywords_max'    => 'Поле Meta Keywords не може бути довшим за 500 символів.',

        'og_title_string' => 'Поле OG Title має бути рядком.',
        'og_title_max'    => 'Поле OG Title не може бути довшим за 255 символів.',

        'og_description_string' => 'Поле OG Description має бути рядком.',
        'og_description_max'    => 'Поле OG Description не може бути довшим за 500 символів.',

        'og_image_string' => 'Поле OG Image має бути рядком.',
        'og_image_max'    => 'Поле OG Image не може бути довшим за 255 символів.',
    ],
];
