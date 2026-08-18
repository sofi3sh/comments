<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $articleTypeId = $this->route('id') ?? $this->route('article-type');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('article_types', 'code')->ignore($articleTypeId),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'is_splittable' => ['sometimes', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('article-type.fields.code'),
            'is_active' => __('article-type.fields.is_active'),
            'is_splittable' => __('article-type.fields.is_splittable'),
            'translations.*.name' => __('article-type-translate.fields.name'),
        ];
    }
}
