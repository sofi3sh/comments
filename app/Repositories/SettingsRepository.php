<?php

namespace App\Repositories;

use App\Models\Settings\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsRepository
{
    private const GLOBAL_CACHE_KEY = 'settings.site.global';

    public function find(?int $siteId, string $key): ?Setting
    {
        return $this->getForSite($siteId)->get($key);
    }

    public function getForSite(?int $siteId): Collection
    {
        return Cache::rememberForever(
            $this->cacheKey($siteId),
            fn() => Setting::query()
                ->where('site_id', $siteId)
                ->get()
                ->keyBy('key')
        );
    }

    public function forgetCache(?int $siteId): void
    {
        Cache::forget($this->cacheKey($siteId));
    }

    private function cacheKey(?int $siteId): string
    {
        return $siteId === null
            ? self::GLOBAL_CACHE_KEY
            : 'settings.site.' . $siteId;
    }
}