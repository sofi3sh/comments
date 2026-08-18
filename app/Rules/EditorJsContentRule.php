<?php

namespace App\Rules;

use App\Editor\EditorJsContentAnalyzer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EditorJsContentRule implements ValidationRule
{
    use RuleTrait;

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {

        if ($value === null || $value === '') {
            return;
        }

        $locale = $this->extractLocaleFromTranslatable($attribute);

        $localeLabel = config("locales.available.$locale")
            ?? strtoupper($locale);

        $attribute = __('article.fields.content')
            . " ($localeLabel)";


        $analyzer = app(EditorJsContentAnalyzer::class);

        if ($analyzer->isEmpty($value)) {

            $fail(
                __('article.validation.editorjs_empty', [
                    'attribute' => $attribute,
                ])
            );
        }
    }
}