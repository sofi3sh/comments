<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkerTranslateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $translationId = $this->route('id') ?? $this->route('marker_translation');
        $markerId = $this->input('marker_id');

        return [
            'marker_id' => ['required', 'integer', 'exists:markers,id'],
            'locale' => [
                'required',
                'string',
                'max:5',
                Rule::unique('marker_translations', 'locale')
                    ->where(fn ($query) => $query->where('marker_id', $markerId))
                    ->ignore($translationId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'marker_id' => __('marker-translation.fields.marker_id'),
            'locale' => __('marker-translation.fields.locale'),
            'name' => __('marker-translation.fields.name'),
        ];
    }

    public function messages(): array
    {
        return [
            'marker_id.required' => __('marker-translation.validation.marker_required'),
            'marker_id.integer' => __('marker-translation.validation.marker_integer'),
            'marker_id.exists' => __('marker-translation.validation.marker_exists'),

            'locale.required' => __('marker-translation.validation.locale_required'),
            'locale.string' => __('marker-translation.validation.locale_string'),
            'locale.max' => __('marker-translation.validation.locale_max'),
            'locale.unique' => __('marker-translation.validation.locale_unique'),

            'name.required' => __('marker-translation.validation.name_required'),
            'name.string' => __('marker-translation.validation.name_string'),
            'name.max' => __('marker-translation.validation.name_max'),
        ];
    }
}

