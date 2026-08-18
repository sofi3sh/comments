<?php

namespace App\Http\Controllers\Traits;

use App\Editor\HtmlToEditorJsContentConverter;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\Settings\Locale;
use App\Services\Article\ArticleFieldConfigurationResolver;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait HasLocaleTab
{

    /*
    |--------------------------------------------------------------------------
    | GENERATE LOCALES TABS
    |--------------------------------------------------------------------------
    */

    /**
     * Generate the locales tabs wrapper
     *
     * @param string $translationClassName
     * @param int|null $parentId
     * @param string $parentIdField
     * @return void
     */
    public function createLocalesTabsWrapper(string $translationClassName, ?int $parentId = null, string $parentIdField = 'article_id'): void
    {
        $fieldPrefix = $this->getFieldPrefix($translationClassName);
        if ($fieldPrefix === '') {
            return;
        }

        $fieldsConfig = $this->getFieldsConfig($translationClassName);
        if (empty($fieldsConfig)) {
            return;
        }

        $locales = Locale::getActive();
        if ($locales->isEmpty()) {
            return;
        }

        // Filter locales by user permissions
        $user = backpack_auth()->user();
        if ($user) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('article_translation_permissions')) {
                    $locales = $locales->filter(function ($locale) use ($user, $parentId) {
                        return \App\Models\Articles\ArticleTranslationPermission::canUserPerformAction(
                            $user->id,
                            $locale->code,
                            $parentId ? 'update' : 'create'
                        );
                    });
                }
            } catch (\Exception $e) {
                // If table doesn't exist, allow all locales
            }
        }

        if ($locales->isEmpty()) {
            return;
        }

        CRUD::addField([
            'name' => 'locales_tabs_wrapper',
            'type' => 'custom_html',
            'value' => $this->generateLocalesTabs($translationClassName, $fieldPrefix, $locales, $fieldsConfig, $parentId, $parentIdField),
            'tab' => __('admin.tabs.translations'),
        ]);
    }

    /**
     * Generate the locales tabs
     *
     * @param string $translationClassName
     * @param string $fieldPrefix
     * @param $locales
     * @param array $fieldsConfig
     * @param int|null $parentId
     * @param string $parentIdField
     * @return string
     */
    private function generateLocalesTabs(string $translationClassName, string $fieldPrefix, $locales, array $fieldsConfig, ?int $parentId = null, string $parentIdField = 'article_id'): string
    {
        $localeKeys = $locales->pluck('code')->toArray();
        $firstLocale = $localeKeys[0] ?? null;
        
        if ($firstLocale === null) {
            return '';
        }

        $existingTranslations = [];
        if ($parentId !== null) {
            $existingTranslations = $this->getExistingTranslations($translationClassName, $parentId, $parentIdField);
        }

        return view('admin.shared.locale-tabs', [
            'fieldPrefix' => $fieldPrefix,
            'locales' => $locales,
            'firstLocale' => $firstLocale,
            'fieldsConfig' => $fieldsConfig,
            'existingTranslations' => $existingTranslations,
            'ariaLabel' => 'Перемикання локалізацій',
            'switchFunction' => 'switchLocaleTab',
            'scriptView' => 'admin.locales.script',
            'fieldsView' => 'admin.locales.fields',
        ])->render();
    }



    /*
    |--------------------------------------------------------------------------
    | GET EXISTING TRANSLATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the field prefix
     *
     * @param string $translationClassName
     * @return string
     */
    private function getFieldPrefix(string $translationClassName): string
    {
        if (method_exists($translationClassName, 'getFieldPrefix')) {
            return $translationClassName::getFieldPrefix();
        }
        
        return '';
    }

    /**
     * Get the fields config
     *
     * @param string $translationClassName
     * @return array
     */
    private function getFieldsConfig(string $translationClassName): array
    {
        if (is_a($translationClassName, ArticleTranslation::class, true)) {
            $typeId = property_exists($this, 'typeId') ? $this->typeId : null;

            return ArticleTranslation::getFieldsConfig(
                app(ArticleFieldConfigurationResolver::class)->forType($typeId),
            );
        }

        if (method_exists($translationClassName, 'getFieldsConfig')) {
            $config = $translationClassName::getFieldsConfig();
            return is_array($config) ? $config : [];
        }
        
        return [];
    }

    /**
     * Get the existing translations
     *
     * @param string $translationClassName
     * @param int $parentId
     * @param string $parentIdField
     * @return array
     */
    private function getExistingTranslations(string $translationClassName, int $parentId, string $parentIdField): array
    {
        $translations = $translationClassName::where($parentIdField, $parentId)->get();

        if (is_a($translationClassName, ArticleTranslation::class, true)) {
            $this->prepareArticleTranslationsForEditor($translations);
        }

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->locale] = $translation;
        }

        return $result;
    }

    private function prepareArticleTranslationsForEditor($translations): void
    {
        $converter = app(HtmlToEditorJsContentConverter::class);

        foreach ($translations as $translation) {
            if (!$translation instanceof ArticleTranslation) {
                continue;
            }

            $content = $translation->content;
            if (!$this->isBlankEditorContent($content)) {
                $translation->setAttribute('content_for_editor', $content);
                $translation->setAttribute('content_converted_from_html', false);

                continue;
            }

            $contentHtml = $translation->content_html;
            if (!$this->isBlankEditorContent($contentHtml)) {
                $translation->setAttribute('content_for_editor', $converter->convert($contentHtml));
                $translation->setAttribute('content_converted_from_html', true);

                continue;
            }

            $translation->setAttribute('content_for_editor', '');
            $translation->setAttribute('content_converted_from_html', false);
        }
    }

    private function isBlankEditorContent(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}
