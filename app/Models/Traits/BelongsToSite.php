<?php

namespace App\Models\Traits;

use App\Scopes\SiteScope;

trait BelongsToSite
{
    protected static function bootBelongsToSite()
    {
        static::addGlobalScope(new SiteScope);

        static::creating(function ($model) {
            if (!$model->site_id && app()->bound('currentSite')) {
                $model->site_id = app('currentSite')->id;
            }
        });
    }
}