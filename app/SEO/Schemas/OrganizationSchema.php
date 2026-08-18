<?php

namespace App\SEO\Schemas;

use App\SEO\SchemaIds;
use Spatie\SchemaOrg\Schema;

class OrganizationSchema
{
    public static function make()
    {
        return Schema::organization()
            ->setProperty('@id', SchemaIds::organization())
            ->name(config('app.name'))
            ->url(config('app.url'));
    }
}

