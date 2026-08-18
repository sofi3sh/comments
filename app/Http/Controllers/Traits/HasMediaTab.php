<?php

namespace App\Http\Controllers\Traits;

use App\Models\Settings\Locale;

trait HasMediaTab
{
    protected function generateMediaTabs(
        $article,
        string $fieldName,
        array $fieldConfig,
        string $prefix = ''
    ): string {

        $locales = Locale::active()->get();

        if ($locales->isEmpty()) {
            return '';
        }

        return view('admin.shared.media-tabs', [
            'article' => $article,
            'locales' => $locales,
            'fieldName' => $fieldName,
            'fieldConfig' => $fieldConfig,
            'prefix' => $prefix,
        ])->render();
    }
}