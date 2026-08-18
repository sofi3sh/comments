<?php

namespace App\Services\Settings;

use InvalidArgumentException;

class SettingsDefinition
{
    public const LOCALIZED_TYPES = [
        'localized_text',
        'localized_html',
    ];

    public function all(): array
    {
        return config('settings.definitions', []);
    }

    public function groups(): array
    {
        return config('settings.groups', []);
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function get(string $key): array
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException("Setting definition [{$key}] does not exist.");
        }

        return $this->all()[$key];
    }

    public function group(string $key): string
    {
        return $this->get($key)['group'];
    }

    public function type(string $key): string
    {
        return $this->get($key)['type'];
    }

    public function label(string $key): string
    {
        return __($this->get($key)['label'] ?? $key);
    }

    public function description(string $key): string
    {
        return __($this->get($key)['description'] ?? '');
    }

    public function default(string $key): array
    {
        return $this->get($key)['default'] ?? [];
    }

    public function isLocalized(string $key): bool
    {
        return in_array($this->type($key), self::LOCALIZED_TYPES, true);
    }

    public function groupLabel(string $group): string
    {
        $label = $this->groups()[$group]['label'] ?? $group;

        return __($label);
    }
}