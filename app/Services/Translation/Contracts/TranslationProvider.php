<?php

namespace App\Services\Translation\Contracts;

interface TranslationProvider
{
    /**
     * @param list<string> $texts
     * @return list<string>
     */
    public function translateMany(array $texts, string $sourceLocale, string $targetLocale, string $format = 'text'): array;
}
