<?php

namespace App\Http\Requests\Articles;

use App\Editor\EditorJsContentAnalyzer;
use App\Support\SlugGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

abstract class TranslatableCrudRequest extends FormRequest
{
    /**
     * Remove empty translations.
     */
    protected function cleanupTranslations(array &$data): void
    {
        foreach (($data['translations'] ?? []) as $locale => $translation) {

            if ($this->isEmptyTranslation($translation)) {

                unset($data['translations'][$locale]);
            }
        }
    }


    /**
     * Generate unique translated slugs.
     */
    protected function generateTranslationSlugs(
        array  &$data,
        string $field,
        string $table,
        string $ownerColumn,
        ?int   $ownerId = null,
    ): void
    {

        foreach (($data['translations'] ?? []) as $locale => $translation) {

            $title = $translation[$field] ?? null;

            if (!$title) {
                continue;
            }

            $current = null;

            if ($ownerId !== null) {
                $current = DB::table($table)
                    ->where($ownerColumn, $ownerId)
                    ->where('locale', $locale)
                    ->first(['title', 'slug']);
            }

            if (
                $current
                && !empty($current->slug)
                && (string) $current->title === (string) $title
            ) {
                $data['translations'][$locale]['slug'] = $current->slug;
                continue;
            }

            $data['translations'][$locale]['slug'] =
                SlugGenerator::generateUnique(
                    title: $title,
                    table: $table,
                    locale: $locale,
                    ignoreColumn: $ownerColumn,
                    ignoreId: $ownerId,
                );
        }
    }

    /**
     * Check if translation is empty.
     */
    protected function isEmptyTranslation(
        array $translation
    ): bool
    {
        return empty($translation['title'] ?? null)
            && empty($translation['excerpt'] ?? null)
            && $this->editorAnalyzer()->isEmpty(
                $translation['content'] ?? null
            );
    }


    protected function editorAnalyzer(): EditorJsContentAnalyzer
    {
        return app(EditorJsContentAnalyzer::class);
    }
}