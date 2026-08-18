<?php

namespace App\Http\Controllers\Traits;

use App\Models\Settings\Locale;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \App\Models\Seo\Translate\SeoMetaTranslation;
use \App\Models\Seo\SeoMeta;

trait HasSeoTab
{

    /*
    |--------------------------------------------------------------------------
    | GENERATE SEO TABS
    |--------------------------------------------------------------------------
    */

    /**
     * Generate the SEO tabs wrapper
     *
     * @param string $className
     * @param int $parentId
     * @return void
     */
    public function createSeoTabs(string $className, ?int $parentId = null)
    {
        $operation = $parentId === null ? 'create' : 'update';
        if (!$this->hasSeoTranslationPermission($operation)) {
            return '';
        }

        $fieldPrefix = $this->getFieldSeoPrefix($className);
        if ($fieldPrefix === '') {
            return '';
        }

        $locales = Locale::active()->get();
        if ($locales->isEmpty()) {
            return '';
        }

        $seoFieldsConfig = SeoMetaTranslation::getFieldsConfig();
        if (empty($seoFieldsConfig)) {
            return '';
        }

        CRUD::addField([
            'name' => 'seo_tabs_wrapper',
            'type' => 'custom_html',
            'value' => $this->generateSeoTabs($className, $fieldPrefix, $locales, $seoFieldsConfig, $parentId),
            'tab' => __('admin.tabs.seo'),
        ]);
    }

    /**
     * SEO is edited through the parent entity form, but it has its own
     * permission scope because it changes localized SEO metadata.
     */
    protected function hasSeoTranslationPermission(string $operation): bool
    {
        $user = backpack_user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('Admin', 'web')
            || $user->hasPermissionTo("seo-meta-translation.{$operation}", 'web');
    }

    /**
     * Generate the SEO tabs
     *
     * @param string $className
     * @param string $fieldPrefix
     * @param \Illuminate\Database\Eloquent\Collection $locales
     * @param array $seoFieldsConfig
     * @param int $parentId
     * @return string
     */
    private function generateSeoTabs(string $className, string $fieldPrefix, $locales, array $seoFieldsConfig, ?int $parentId = null): string
    {
        $localeKeys = $locales->pluck('code')->toArray();
        $firstLocale = $localeKeys[0] ?? null;

        if ($firstLocale === null) {
            return '';
        }

        $existingTranslations = [];
        if ($parentId !== null) {
            $existingTranslations = $this->getExistingSeoTranslations($className, $parentId);
        }

        return view('admin.shared.locale-tabs', [
            'fieldPrefix' => $fieldPrefix,
            'locales' => $locales,
            'firstLocale' => $firstLocale,
            'fieldsConfig' => $seoFieldsConfig,
            'existingTranslations' => $existingTranslations,
            'ariaLabel' => 'Перемикання локалізацій для SEO',
            'switchFunction' => 'switchSeoTab',
            'scriptView' => 'admin.seo.script',
            'fieldsView' => 'admin.seo.fields',
        ])->render();
    }



    /*
    |--------------------------------------------------------------------------
    | GET EXISTING SEO TRANSLATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the field SEO prefix
     *
     * @param string $className
     * @return string
     */
    private function getFieldSeoPrefix(string $className): string
    {
        if (method_exists($className, 'getFieldSeoPrefix')) {
            return $className::getFieldSeoPrefix();
        }
        
        return '';
    }

    /**
     * Get the existing SEO translations
     *
     * @param string $className
     * @param int $parentId
     * @return array
     */
    private function getExistingSeoTranslations(string $className, int $parentId): array
    {
        $entityType = $className;
        
        $seoMeta = SeoMeta::where('entity_type', $entityType)
            ->where('entity_id', $parentId)
            ->first();

        if (!$seoMeta) {
            return [];
        }

        $translations = SeoMetaTranslation::where('seo_meta_id', $seoMeta->id)->get();

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->locale] = $translation;
        }

        return $result;
    }

}
