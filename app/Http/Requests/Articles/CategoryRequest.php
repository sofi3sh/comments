<?php

namespace App\Http\Requests\Articles;

use App\Support\Validation\SeoValidation;
use Illuminate\Validation\Rule;

class CategoryRequest extends TranslatableCrudRequest
{

    public function authorize(): bool
    {
        return backpack_auth()->check();
    }


    public function rules(): array
    {
        $categoryId = (int) request()->route('id');

        $rules = [
            'site_id' => [
                'nullable',
                'integer',
                'exists:sites,id'
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                Rule::notIn([$categoryId]),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
            ],
            'translations' => [
                'nullable',
                'array',
                'min:1',
            ],
            'translations.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'translations.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        return array_merge($rules, SeoValidation::rules());
    }

    public function attributes(): array
    {
        return [
            'site_id'   => __('category.fields.site_id'),
            'parent_id' => __('category.fields.parent_id'),
            'slug'      => __('category.fields.slug'),
        ];
    }

    public function messages(): array
    {
        return [
            'site_id.required' => __('category.validation.site_required'),
            'site_id.integer'  => __('category.validation.site_integer'),
            'site_id.exists'   => __('category.validation.site_exists'),

            'parent_id.integer' => __('category.validation.parent_integer'),
            'parent_id.exists'  => __('category.validation.parent_exists'),
            'parent_id.not_in'  => __('category.validation.parent_not_in'),

            'slug.required' => __('category.validation.slug_required'),
            'slug.string'   => __('category.validation.slug_string'),
            'slug.max'      => __('category.validation.slug_max'),
        ];
    }
}

