<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CurrentSiteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /*
         |--------------------------------------------------------------------------
         | Skip admin / console
         |--------------------------------------------------------------------------
         */

        if (
            app()->runningInConsole()
            || is_admin_request()
        ) {
            return;
        }

        /*
         |--------------------------------------------------------------------------
         | Skip if currentSite missing
         |--------------------------------------------------------------------------
         */
        if (! app()->bound('currentSite')) {
            return;
        }

        $site = app('currentSite');

        $builder->whereHas('sites', function ($query) use ($site) {
            $query->where('sites.id', $site->id);
        });
    }
}