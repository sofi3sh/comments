<?php

namespace App\SEO\Schemas;

use App\SEO\SchemaIds;
use Spatie\SchemaOrg\Schema;

class WebSiteSchema
{
    public static function make()
    {
        return Schema::website()
            ->setProperty('@id', SchemaIds::website())
            ->url(config('app.url'))
            ->publisher([
                '@id' => SchemaIds::organization(),
            ]);
    }
}

