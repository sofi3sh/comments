<?php

return [
    'admin' => [
        'title_in_singular' => 'Article Translate',
        'title_in_plural' => 'Article Translates',
    ],

    'fields' => [
        'article_id' => 'Article',
        'locale' => 'Locale',
        'title' => 'Title',
        'excerpt' => 'Excerpt',
        'content' => 'Content',
        'slug' => 'Slug',
        'created_at' => __('admin.fields.created_at'),
        'updated_at' => __('admin.fields.updated_at'),
    ],

    'validation' => [
        'article_required' => 'Please select an article.',
        'article_integer'  => 'The article field must be an ID value.',
        'article_exists'   => 'The selected article does not exist.',

        'locale_required' => 'Please choose a locale.',
        'locale_string'   => 'The locale must be a string.',
        'locale_max'      => 'The locale may not be greater than 5 characters.',
        'locale_unique'   => 'This locale already has a translation for the selected article.',

        'title_required' => 'The title field is required.',
        'title_string'   => 'The title must be a string.',
        'title_max'      => 'The title may not be greater than 255 characters.',

        'excerpt_string' => 'The excerpt must be a string.',
        'content_string' => 'The content must be a string.',

        'slug_string' => 'The slug must be a string.',
        'slug_max'    => 'The slug may not be greater than 255 characters.',
    ],

    'auto_translate' => [
        'source_locale' => 'Source locale',
        'target_locale' => 'Target locale',
        'source' => 'Source translation',
        'target' => 'Target translation',
        'overwrite' => 'Overwrite filled fields',
        'overwrite_confirm' => 'Overwrite translation?',
        'button' => 'Auto translate',
        'loading' => 'Translating...',
        'success' => 'Translation applied.',
        'skipped' => 'Skipped',
        'config_error' => 'Auto translate is not configured.',
        'same_locale' => 'Choose different locales.',
        'content_too_large' => 'Content is too large for synchronous translation. Limit: :limit characters.',
    ],
];
