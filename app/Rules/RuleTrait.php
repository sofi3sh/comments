<?php

namespace App\Rules;

trait RuleTrait
{
    protected function extractLocaleFromTranslatable(string $attribute): ?string
    {
        return explode('.', $attribute)[1] ?? null;
    }
}