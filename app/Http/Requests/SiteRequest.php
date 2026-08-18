<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteRequest extends FormRequest
{

    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $siteId = $this->route('id') ?? $this->route('site');

        return [
            'name'            => ['required', 'string', 'max:255'],
            'slug'            => [
                'required',
                'string',
                'max:100',
                Rule::unique('sites', 'slug')->ignore($siteId),
            ],
            'domain'          => [
                'required',
                'string',
                'max:255',
                Rule::unique('sites', 'domain')->ignore($siteId),
            ],
            'color_primary'   => ['nullable', 'string', 'max:20'],
            'color_secondary' => ['nullable', 'string', 'max:20'],
            'active'          => ['sometimes', 'boolean'],
        ];
    }

    public function attributes()
    {
        return [
            'name'            => __('site.admin.fields.name'),
            'slug'            => __('site.admin.fields.slug'),
            'domain'          => __('site.admin.fields.domain'),
            'color_primary'   => __('site.admin.fields.color_primary'),
            'color_secondary' => __('site.admin.fields.color_secondary'),
            'active'          => __('site.admin.fields.active'),
        ];
    }

    public function messages()
    {
        return [
            'name.required'  => __('site.admin.validation.name_required'),
            'name.string'    => __('site.admin.validation.name_string'),
            'name.max'       => __('site.admin.validation.name_max'),

            'slug.required' => __('site.admin.validation.slug_required'),
            'slug.string'   => __('site.admin.validation.slug_string'),
            'slug.max'      => __('site.admin.validation.slug_max'),
            'slug.unique'   => __('site.admin.validation.slug_unique'),

            'domain.required' => __('site.admin.validation.domain_required'),
            'domain.string'   => __('site.admin.validation.domain_string'),
            'domain.max'      => __('site.admin.validation.domain_max'),
            'domain.unique'   => __('site.admin.validation.domain_unique'),

            'color_primary.string'   => __('site.admin.validation.color_primary_string'),
            'color_primary.max'      => __('site.admin.validation.color_primary_max'),
            'color_secondary.string' => __('site.admin.validation.color_secondary_string'),
            'color_secondary.max'    => __('site.admin.validation.color_secondary_max'),

            'active.boolean' => __('site.admin.validation.active_boolean'),
        ];
    }
}