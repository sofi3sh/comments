<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleAutoTranslateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $locales = array_keys(config('locales.available', []));

        return [
            'source_locale' => ['required', 'string', Rule::in($locales)],
            'target_locale' => ['required', 'string', 'different:source_locale', Rule::in($locales)],
            'overwrite' => ['sometimes', 'boolean'],
            'source' => ['required', 'array'],
            'source.title' => ['nullable', 'string'],
            'source.excerpt' => ['nullable', 'string'],
            'source.content' => ['nullable', 'string'],
            'target' => ['sometimes', 'array'],
            'target.title' => ['nullable', 'string'],
            'target.excerpt' => ['nullable', 'string'],
            'target.content' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'source_locale' => __('article-translate.auto_translate.source_locale'),
            'target_locale' => __('article-translate.auto_translate.target_locale'),
            'source' => __('article-translate.auto_translate.source'),
            'target' => __('article-translate.auto_translate.target'),
        ];
    }
}
