<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleFieldConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $configId = $this->route('id') ?? $this->route('article-field-configuration');
        $articleTypeId = $this->input('article_type_id');

        return [
            'article_type_id' => ['nullable', 'integer', 'exists:article_types,id'],
            'field_name' => [
                'required',
                'string',
                'max:100',
                Rule::in([
                    'title', 'excerpt', 'content', 'slug',
                    'site_id', 'category_id', 'type_id', 'status', 'published_at',
                    'thumbnail', 'source_url',
                    'authors', 'editors', 'tags', 'markers',

                    'youtube_id',

                    'company_edrpou',
                    'company_website',
                    'company_social',
                    'company_phone',
                    'company_director',
                    'company_position',
                    'company_type',
                ]),
                Rule::unique('article_field_configurations', 'field_name')
                    ->ignore($configId)
                    ->when(
                        $articleTypeId === null || $articleTypeId === '',
                        fn ($rule) => $rule->whereNull('article_type_id'),
                        fn ($rule) => $rule->where('article_type_id', (int) $articleTypeId),
                    ),
            ],
            'is_required' => ['sometimes', 'boolean'],
            'is_visible' => ['sometimes', 'boolean'],
            'min_length' => ['nullable', 'integer', 'min:0'],
            'max_length' => ['nullable', 'integer', 'min:1'],
            'validation_rules_string' => ['nullable', 'string'],
            'validation_rules' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'field_name' => __('article-field-configuration.fields.field_name'),
            'article_type_id' => __('article-field-configuration.fields.article_type_id'),
            'is_required' => __('article-field-configuration.fields.is_required'),
            'is_visible' => __('article-field-configuration.fields.is_visible'),
            'min_length' => __('article-field-configuration.fields.min_length'),
            'max_length' => __('article-field-configuration.fields.max_length'),
            'validation_rules' => __('article-field-configuration.fields.validation_rules'),
            'position' => __('article-field-configuration.fields.position'),
        ];
    }
}
