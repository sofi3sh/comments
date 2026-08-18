<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryTranslateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $translationId = $this->route('id') ?? $this->route('category_translation');
        $categoryId    = $this->input('category_id');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'locale'      => [
                'required',
                'string',
                'max:5',
                Rule::unique('category_translations', 'locale')
                    ->where(fn ($query) => $query->where('category_id', $categoryId))
                    ->ignore($translationId),
            ],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug'        => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => __('category-translation.fields.category_id'),
            'locale'      => __('category-translation.fields.locale'),
            'name'        => __('category-translation.fields.name'),
            'description' => __('category-translation.fields.description'),
            'slug'        => __('category-translation.fields.slug'),
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => __('category-translation.validation.category_required'),
            'category_id.integer'  => __('category-translation.validation.category_integer'),
            'category_id.exists'   => __('category-translation.validation.category_exists'),

            'locale.required' => __('category-translation.validation.locale_required'),
            'locale.string'   => __('category-translation.validation.locale_string'),
            'locale.max'      => __('category-translation.validation.locale_max'),
            'locale.unique'   => __('category-translation.validation.locale_unique'),

            'name.required' => __('category-translation.validation.name_required'),
            'name.string'   => __('category-translation.validation.name_string'),
            'name.max'      => __('category-translation.validation.name_max'),

            'description.string' => __('category-translation.validation.description_string'),

            'slug.string' => __('category-translation.validation.slug_string'),
            'slug.max'    => __('category-translation.validation.slug_max'),
        ];
    }
}

