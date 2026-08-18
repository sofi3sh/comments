<?php
namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocaleRequest extends FormRequest
{

    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $localeId = $this->route('id') ?? $this->route('settings-locale');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locales', 'name')->ignore($localeId),
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locales', 'code')->ignore($localeId),
            ],
            'prefix' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('settings.locale.fields.name'),
            'code' => __('settings.locale.fields.code'),
            'prefix' => __('settings.locale.fields.prefix'),
            'is_default' => __('settings.locale.fields.is_default'),
            'is_active' => __('settings.locale.fields.is_active'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('settings.locale.validation.name_required'),
            'name.string' => __('settings.locale.validation.name_string'),
            'name.max' => __('settings.locale.validation.name_max'),
            'name.unique' => __('settings.locale.validation.name_unique'),

            'code.required' => __('settings.locale.validation.code_required'),
            'code.string' => __('settings.locale.validation.code_string'),
            'code.max' => __('settings.locale.validation.code_max'),
            'code.unique' => __('settings.locale.validation.code_unique'),

            'prefix.string' => __('settings.locale.validation.prefix_string'),
            'prefix.max' => __('settings.locale.validation.prefix_max'),

            'is_default.boolean' => __('settings.locale.validation.is_default_boolean'),

            'is_active.required' => __('settings.locale.validation.is_active_required'),
            'is_active.boolean' => __('settings.locale.validation.is_active_boolean'),
        ];
    }
}