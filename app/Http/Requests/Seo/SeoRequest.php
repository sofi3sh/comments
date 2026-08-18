<?php

namespace App\Http\Requests\Seo;

use Illuminate\Foundation\Http\FormRequest;

class SeoRequest extends FormRequest
{

    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        return [
            'entity_type' => ['required', 'string', 'max:50'],
            'entity_id'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes()
    {
        return [
            'entity_type' => __('seo-meta.fields.entity_type'),
            'entity_id'   => __('seo-meta.fields.entity_id'),
        ];
    }

    public function messages()
    {
        return [
            'entity_type.required' => __('seo-meta.validation.entity_type_required'),
            'entity_type.string'   => __('seo-meta.validation.entity_type_string'),
            'entity_type.max'      => __('seo-meta.validation.entity_type_max'),

            'entity_id.required' => __('seo-meta.validation.entity_id_required'),
            'entity_id.integer'  => __('seo-meta.validation.entity_id_integer'),
            'entity_id.min'      => __('seo-meta.validation.entity_id_min'),
        ];
    }
}