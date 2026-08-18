<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface LocalizedUrlContract
{
    public function getAvailableLocales(): Collection;

    public function getItemUrlForLocale(string $locale): string;

}