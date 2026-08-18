<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SiteScope implements Scope
{

    /**
     * @inheritDoc
     */
    public function apply(Builder $builder, Model $model)
    {
        // The admin panel is served from a prefix on a site domain, so
        // currentSite is bound there too — but admin listings must show every
        // site's records. Same exemption as CurrentSiteScope.
        if (app()->runningInConsole() || is_admin_request()) {
            return;
        }

        if (app()->bound('currentSite')) {
            $builder->where(
                $model->getTable() . '.site_id',
                app('currentSite')->id
            );
        }
    }
}
