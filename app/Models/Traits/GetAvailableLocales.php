<?php

namespace App\Models\Traits;

use App\Models\Settings\Locale;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

trait GetAvailableLocales
{
    /**
     * Get available locales HTML with icons from locale codes
     *
     * @param array $localeCodes
     * @return string
     */
    protected function getAvailableLocalesHtml(array $localeCodes): string
    {
        if (empty($localeCodes)) {
            return '';
        }
        
        $locales = Locale::whereIn('code', $localeCodes)->get();
        
        $localesData = [];
        foreach ($locales as $locale) {
            $localesData[] = [
                'code' => $locale->code,
                'name' => $locale->name,
                'icon_url' => $locale->icon ? Storage::disk('public')->url($locale->icon) : null,
            ];
        }
        
        return View::make('admin.shared.list-locales', ['locales' => $localesData])->render();
    }
}

