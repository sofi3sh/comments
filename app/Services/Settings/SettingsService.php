<?php

namespace App\Services\Settings;

class SettingsService
{
    public function __construct(
        private readonly SettingsDefinition    $definition,
        private readonly SettingsValueResolver $resolver,
    )
    {
    }

    public function get(string $key, ?int $siteId = null, ?string $locale = null): mixed
    {
        return $this->resolver->value(
            $key,
            $siteId ?? $this->currentSiteId(),
            $locale
        );
    }

    public function label(string $key, ?int $siteId = null, ?string $locale = null): ?string
    {
        return $this->resolver->label(
            $key,
            $siteId ?? $this->currentSiteId(),
            $locale
        );
    }

    public function description(string $key, ?int $siteId = null, ?string $locale = null): ?string
    {
        return $this->resolver->description(
            $key,
            $siteId ?? $this->currentSiteId(),
            $locale
        );
    }

    public function isActive(string $key, ?int $siteId = null): bool
    {
        return $this->resolver->isActive(
            $key,
            $siteId ?? $this->currentSiteId()
        );
    }

    public function definition(string $key): array
    {
        return $this->definition->get($key);
    }

    public function definitions(): array
    {
        return $this->definition->all();
    }

    public function keys(): array
    {
        return $this->definition->keys();
    }

    private function currentSiteId(): ?int
    {
        $currentSite = app('currentSite');

        return $currentSite?->id;
    }
}