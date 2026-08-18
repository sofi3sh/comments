<?php

namespace App\Rules;

use App\Editor\EditorJsContentAnalyzer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class EditorJsMinTextLengthRule implements ValidationRule
{
    use RuleTrait;

    /**
     * Створює правило з мінімальною кількістю текстових символів.
     */
    public function __construct(private readonly int $minimum)
    {
    }

    /**
     * Перевіряє текстову довжину контенту EditorJS для конкретної локалі.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (app(EditorJsContentAnalyzer::class)->textLength((string) $value) >= $this->minimum) {
            return;
        }

        $locale = $this->extractLocaleFromTranslatable($attribute);
        $localeLabel = config("locales.available.$locale") ?? strtoupper((string) $locale);

        $fail(__('article.validation.content_min_length', [
            'attribute' => __('article.fields.content') . " ($localeLabel)",
            'min' => $this->minimum,
        ]));
    }
}
