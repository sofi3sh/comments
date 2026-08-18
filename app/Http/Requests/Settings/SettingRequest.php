<?php

namespace App\Http\Requests\Settings;

use App\Models\Settings\Locale;
use App\Models\Settings\Setting;
use App\Services\Settings\SettingsDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $rules = $this->isCreate()
            ? $this->createRules()
            : $this->updateRules();

        if (! $this->isCreate()) {
            $rules = array_merge($rules, $this->valueRules());
        }

        return $rules;
    }

    private function isCreate(): bool
    {
        return $this->currentSetting() === null;
    }

    private function createRules(): array
    {
        return [
            'site_id' => ['nullable', 'exists:sites,id'],
            'key' => [
                'required',
                'string',
                Rule::in(app(SettingsDefinition::class)->keys()),
                $this->uniqueSiteKeyRule(),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function updateRules(): array
    {
        $rules = [
            'value'       => ['nullable', 'array'],
            'is_active'   => ['nullable', 'boolean'],
        ];

        return array_merge($rules, $this->valueRules());
    }

    private function valueRules(): array
    {
        $setting = $this->currentSetting();

        $key = $setting?->key ?? $this->input('key');

        if (! $key) {
            return [];
        }

        $definition = app(SettingsDefinition::class);
        $type = $definition->type($key);

        return match ($type) {

            'localized_html' => $this->localizedStringRules(),
            'localized_text' => $this->localizedStringRules(),

            'phone' => [
                'value.value' => ['nullable', 'string', 'max:255'],
            ],

            'email' => [
                'value.value' => ['nullable', 'email', 'max:255'],
            ],

            'boolean' => [
                'value.value' => ['nullable', 'boolean'],
            ],

            'social_links' => $this->socialLinksRules($definition->default($key)),

            default => [],
        };
    }

    private function localizedStringRules(?int $max = null): array
    {
        $rules = [];

        foreach (Locale::getAvailableAsArr() as $locale) {
            $rules["value.$locale"] = $max
                ? ['nullable', 'string', 'max:' . $max]
                : ['nullable', 'string'];
        }

        return $rules;
    }

    private function socialLinksRules(array $default): array
    {
        $rules = [
            'value' => ['nullable', 'array'],
        ];

        foreach (array_keys($default) as $network) {
            $rules["value.$network.enabled"] = ['nullable', 'boolean'];
            $rules["value.$network.url"] = ['nullable', 'url', 'max:255'];
        }

        return $rules;
    }

    private function currentSetting(): ?Setting
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        return Setting::query()->find($id);
    }

    private function uniqueSiteKeyRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $query = Setting::query()->where('key', $value);

            if ($this->filled('site_id')) {
                $query->where('site_id', $this->input('site_id'));
            } else {
                $query->whereNull('site_id');
            }

            if ($query->exists()) {
                $fail('Setting already exists for selected site.');
            }
        };
    }
}