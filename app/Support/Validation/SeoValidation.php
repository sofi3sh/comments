<?php

namespace App\Support\Validation;

class SeoValidation
{
    public static function rules(string $prefix = 'seo'): array
    {
        return [
            "{$prefix}.*.meta_title" => [
                'nullable',
                'string',
                'max:255',
            ],

            "{$prefix}.*.meta_description" => [
                'nullable',
                'string',
                'max:255',
            ],

            "{$prefix}.*.meta_keywords" => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}