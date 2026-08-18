<?php

namespace App\Http\Controllers\Traits;

use App\Services\Seo\SeoService;
use Illuminate\Database\Eloquent\Model;

trait CrudTrait
{

    protected function saveSeo(Model $item, array $seo): void
    {
        $operation = $item->wasRecentlyCreated ? 'create' : 'update';
        if (!$this->hasSeoTranslationPermission($operation)) {
            return;
        }

        app(SeoService::class)->save($item, $seo);
    }

    protected function saveTranslation(Model $item, array $translations): void
    {
        $item->fill($translations);
        $item->save();
    }
}
