<?php

namespace App\Http\Requests\Articles;

use App\Models\Articles\Marker;
use App\Rules\TranslatableUniqueRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $markerId = $this->route('id') ?? $this->route('marker');
        $marker = $markerId !== null
            ? Marker::query()->find($markerId)
            : null;
        $isSystemMarker = $marker?->isSystem() ?? false;

        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf($isSystemMarker),
                Rule::unique('markers', 'code')->ignore($markerId),
                ...($isSystemMarker ? [Rule::in([$marker->code])] : []),
            ],
            'icon' => [
                'nullable',
                'string'
            ],
            'is_active' => $isSystemMarker
                ? ['prohibited']
                : ['sometimes', 'boolean'],
            'is_system' => ['prohibited'],
            'translations.*.name' => [
                'required',
                'string',
                'max:255',
                new TranslatableUniqueRule(
                    table: 'marker_translations',
                    ownerColumn: 'marker_id',
                    ownerId: $markerId,
                    column: 'name',
                ),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('marker.fields.code'),
            'icon' => __('marker.fields.icon'),
            'is_active' => __('marker.fields.is_active'),
            'is_system' => __('marker.fields.is_system'),
        ];
    }
}
