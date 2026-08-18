<?php


namespace App\Services\Article\Validation;

use App\Editor\EditorJsContentAnalyzer;
use App\Models\Settings\Locale;
use Illuminate\Contracts\Validation\Validator;

final class PublishWorkflowValidator
{
    public function validate(array $input, Validator $validator): void
    {
        if (!empty($input['status']) && $input['status'] !== 'published') {
            return;
        }

        if (empty($input['category_id'])) {
            $validator->errors()->add(
                'category_id',
                __('article.validation.category_required')
            );
        }

        if (empty($input['markers'])) {
            $validator->errors()->add(
                'markers',
                __('article.validation.markers_required')
            );
        }

        if (empty($input['source_url'])) {
            $validator->errors()->add(
                'source_url',
                __('article.validation.source_required')
            );
        }

        if (empty($input['sites'])) {
            $validator->errors()->add(
                'sites',
                __('article.validation.sites_required')
            );
        }

        if (empty($input['published_at'])) {
            $validator->errors()->add(
                'published_at',
                __('article.validation.published_at_required')
            );
        }

        $hasTranslation = false;

        foreach (Locale::getAvailableAsArr() as $locale) {

            $title = trim((string) $input["translations"][$locale]['title']);
            $content = $input["translations"][$locale]['content'];

            $hasContent = app(EditorJsContentAnalyzer::class)
                    ->isEmpty($content) === false;

            if ($title !== '' && $hasContent) {
                $hasTranslation = true;
                break;
            }
        }

        if (! $hasTranslation) {
            $validator->errors()->add(
                'translations',
                __('article.validation.translation_required')
            );
        }
    }
}