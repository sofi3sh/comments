<?php

namespace App\Services\Settings;

use App\Repositories\SettingsRepository;

class SettingsValueResolver
{
    /**
     *  Main Rule  если site/global setting существует и is_active = false → возвращаем null / []
     *  fallback дальше не идет
     */

    public function __construct(
        private readonly SettingsDefinition $definition,
        private readonly SettingsRepository $repository,
    )
    {
    }

    public function value(string $key, ?int $siteId = null, ?string $locale = null): mixed
    {
        $setting = $this->resolveSetting($key, $siteId);

        if ($setting === false) {
            return $this->emptyValue($key);
        }

        $value = $setting?->value ?? $this->definition->default($key);

        return $this->resolveValueByType($key, $value, $locale);
    }

    public function label(string $key, ?int $siteId = null, ?string $locale = null): ?string
    {
        $setting = $this->resolveSetting($key, $siteId);

        $labels = $setting && $setting !== false
            ? $setting->label
            : null;

        return $this->localizedMeta(
            $labels,
            $this->definition->label($key),
            $locale
        );
    }

    public function description(string $key, ?int $siteId = null, ?string $locale = null): ?string
    {
        $setting = $this->resolveSetting($key, $siteId);

        $descriptions = $setting && $setting !== false
            ? $setting->description
            : null;

        return $this->localizedMeta(
            $descriptions,
            $this->definition->description($key),
            $locale
        );
    }

    public function isActive(string $key, ?int $siteId = null): bool
    {
        return $this->resolveSetting($key, $siteId) !== false;
    }

    private function resolveSetting(string $key, ?int $siteId): mixed
    {
        $this->definition->get($key);

        if ($siteId !== null) {
            $siteSetting = $this->repository->find($siteId, $key);

            if ($siteSetting) {
                return $siteSetting->is_active ? $siteSetting : false;
            }
        }

        $globalSetting = $this->repository->find(null, $key);

        if ($globalSetting) {
            return $globalSetting->is_active ? $globalSetting : false;
        }

        return null;
    }

    private function resolveValueByType(string $key, array $value, ?string $locale = null): mixed
    {
        if ($this->definition->isLocalized($key)) {
            return $this->localizedValue($value, $locale);
        }

        return match ($this->definition->type($key)) {
            'phone', 'email' => $value['value'] ?? null,
            'boolean' => $this->booleanValue($value),
            'social_links' => $this->enabledSocialLinks($value),
            default => $value['value'] ?? $value,
        };
    }

    private function localizedValue(array $value, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $value[$locale]
            ?? $value['uk']
            ?? $value['en']
            ?? $value['ru']
            ?? null;
    }

    private function localizedMeta(?array $custom, array $default, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $custom[$locale]
            ?? $default[$locale]
            ?? $custom['uk']
            ?? $default['uk']
            ?? $custom['en']
            ?? $default['en']
            ?? $custom['ru']
            ?? $default['ru']
            ?? null;
    }

    private function booleanValue(array $value): bool
    {
        return filter_var($value['value'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function enabledSocialLinks(array $value): array
    {
        return collect($value)
            ->filter(fn(array $item) => ($item['enabled'] ?? false) && !empty($item['url']))
            ->all();
    }

    private function emptyValue(string $key): mixed
    {
        return match ($this->definition->type($key)) {
            'social_links' => [],
            default => null,
        };
    }
}