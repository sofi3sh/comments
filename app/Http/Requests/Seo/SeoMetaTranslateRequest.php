<?php

namespace App\Http\Requests\Seo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeoMetaTranslateRequest extends FormRequest
{

    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $translationId = $this->route('id') ?? $this->route('seo_meta_translate');
        $seoMetaId     = $this->input('seo_meta_id');

        return [
            'seo_meta_id'      => ['required', 'integer', 'exists:seo_meta,id'],
            'locale'           => [
                'required',
                'string',
                'max:5',
                Rule::unique('seo_meta_translations', 'locale')
                    ->where(fn ($query) => $query->where('seo_meta_id', $seoMetaId))
                    ->ignore($translationId),
            ],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords'    => ['nullable', 'string', 'max:500'],
            'og_title'         => ['nullable', 'string', 'max:255'],
            'og_description'   => ['nullable', 'string', 'max:500'],
            'og_image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];
    }

    public function attributes()
    {
        return [
            'seo_meta_id'      => __('seo-meta-translate.fields.seo_meta_id'),
            'locale'           => __('seo-meta-translate.fields.locale'),
            'meta_title'       => __('seo-meta-translate.fields.meta_title'),
            'meta_description' => __('seo-meta-translate.fields.meta_description'),
            'meta_keywords'    => __('seo-meta-translate.fields.meta_keywords'),
            'og_title'         => __('seo-meta-translate.fields.og_title'),
            'og_description'   => __('seo-meta-translate.fields.og_description'),
            'og_image'         => __('seo-meta-translate.fields.og_image'),
        ];
    }

    public function messages()
    {
        return [
            'seo_meta_id.required' => __('seo-meta-translate.validation.seo_meta_required'),
            'seo_meta_id.integer'  => __('seo-meta-translate.validation.seo_meta_integer'),
            'seo_meta_id.exists'   => __('seo-meta-translate.validation.seo_meta_exists'),

            'locale.required' => __('seo-meta-translate.validation.locale_required'),
            'locale.string'   => __('seo-meta-translate.validation.locale_string'),
            'locale.max'      => __('seo-meta-translate.validation.locale_max'),
            'locale.unique'   => __('seo-meta-translate.validation.locale_unique'),

            'meta_title.string'       => __('seo-meta-translate.validation.meta_title_string'),
            'meta_title.max'          => __('seo-meta-translate.validation.meta_title_max'),

            'meta_description.string' => __('seo-meta-translate.validation.meta_description_string'),
            'meta_description.max'    => __('seo-meta-translate.validation.meta_description_max'),

            'meta_keywords.string' => __('seo-meta-translate.validation.meta_keywords_string'),
            'meta_keywords.max'    => __('seo-meta-translate.validation.meta_keywords_max'),

            'og_title.string' => __('seo-meta-translate.validation.og_title_string'),
            'og_title.max'    => __('seo-meta-translate.validation.og_title_max'),

            'og_description.string' => __('seo-meta-translate.validation.og_description_string'),
            'og_description.max'    => __('seo-meta-translate.validation.og_description_max'),

            'og_image.string' => __('seo-meta-translate.validation.og_image_string'),
            'og_image.max'    => __('seo-meta-translate.validation.og_image_max'),
        ];
    }
}