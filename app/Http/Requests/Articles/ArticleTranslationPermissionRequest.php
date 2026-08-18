<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleTranslationPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $permissionId = $this->route('id') ?? $this->route('article-translation-permission');

        return [
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],
            'locale' => [
                'required',
                'string',
                'max:5',
                Rule::in(['en', 'ru', 'uk']),
                Rule::unique('article_translation_permissions', 'locale')
                    ->where(fn ($query) => $query->where('role_id', $this->input('role_id')))
                    ->ignore($permissionId),
            ],
            'can_create' => ['sometimes', 'boolean'],
            'can_update' => ['sometimes', 'boolean'],
            'can_delete' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'role_id' => __('article-translation-permission.fields.role'),
            'locale' => __('article-translation-permission.fields.locale'),
            'can_create' => __('article-translation-permission.fields.can_create'),
            'can_update' => __('article-translation-permission.fields.can_update'),
            'can_delete' => __('article-translation-permission.fields.can_delete'),
        ];
    }
}

