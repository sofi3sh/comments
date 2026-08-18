<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleTranslateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $translationId = $this->route('id') ?? $this->route('article_translation');
        $articleId     = $this->input('article_id');

        $rules = [
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            'locale'     => [
                'required',
                'string',
                'max:5',
                Rule::unique('article_translations', 'locale')
                    ->where(fn ($query) => $query->where('article_id', $articleId))
                    ->ignore($translationId),
            ],
        ];

        // Get dynamic validation rules from configuration
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('article_field_configurations')) {
                $fieldConfigs = \App\Models\Articles\ArticleFieldConfiguration::all()->keyBy('field_name');
                
                foreach (['title', 'excerpt', 'content', 'slug'] as $field) {
                    $config = $fieldConfigs->get($field);
                    if ($config) {
                        $rules[$field] = $config->getValidationRulesArray();
                    } else {
                        // Default rules if no configuration
                        $rules[$field] = $field === 'title' 
                            ? ['required', 'string', 'max:255']
                            : ['nullable', 'string'];
                    }
                }
            } else {
                // Default rules if table doesn't exist
                $rules['title'] = ['required', 'string', 'max:255'];
                $rules['excerpt'] = ['nullable', 'string'];
                $rules['content'] = ['nullable', 'string'];
                $rules['slug'] = ['nullable', 'string', 'max:255'];
            }
        } catch (\Exception $e) {
            // Default rules if error
            $rules['title'] = ['required', 'string', 'max:255'];
            $rules['excerpt'] = ['nullable', 'string'];
            $rules['content'] = ['nullable', 'string'];
            $rules['slug'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'article_id' => __('article-translate.fields.article_id'),
            'locale'     => __('article-translate.fields.locale'),
            'title'      => __('article-translate.fields.title'),
            'excerpt'    => __('article-translate.fields.excerpt'),
            'content'    => __('article-translate.fields.content'),
            'slug'       => __('article-translate.fields.slug'),
        ];
    }

    public function messages(): array
    {
        return [
            'article_id.required' => __('article-translate.validation.article_required'),
            'article_id.integer'  => __('article-translate.validation.article_integer'),
            'article_id.exists'   => __('article-translate.validation.article_exists'),

            'locale.required' => __('article-translate.validation.locale_required'),
            'locale.string'   => __('article-translate.validation.locale_string'),
            'locale.max'      => __('article-translate.validation.locale_max'),
            'locale.unique'   => __('article-translate.validation.locale_unique'),

            'title.required' => __('article-translate.validation.title_required'),
            'title.string'   => __('article-translate.validation.title_string'),
            'title.max'      => __('article-translate.validation.title_max'),

            'excerpt.string' => __('article-translate.validation.excerpt_string'),
            'content.string' => __('article-translate.validation.content_string'),

            'slug.string' => __('article-translate.validation.slug_string'),
            'slug.max'    => __('article-translate.validation.slug_max'),
        ];
    }
}

