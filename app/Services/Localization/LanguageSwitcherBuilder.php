<?php

namespace App\Services\Localization;

use App\Contracts\LocalizedUrlContract;
use App\Helpers\LocaleUrlHelper;
use App\Models\Settings\Locale;

class LanguageSwitcherBuilder
{
    public function build(
        ?LocalizedUrlContract $model = null
    ): array
    {
        $availableLocales = Locale::getActive();
        $currentLocale = app()->getLocale();

        if ($model) {
            $availableLocales = $model->getAvailableLocales();
        }

        return $availableLocales
            ->map(function ($locale) use ($currentLocale, $model) {

                return [
                    'code' => $locale->code,
                    'name' => strtoupper($locale->code),
                    'native' => $locale->name ?? strtoupper($locale->code),
                    'isActive' => $locale->code === $currentLocale,

                    'url' => $model
                        ? $model->getItemUrlForLocale($locale->code)
                        : LocaleUrlHelper::localizedUrl($locale->code),
                ];
            })
            ->values()
            ->toArray();
    }
}