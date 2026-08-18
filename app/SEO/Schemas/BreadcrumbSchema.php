<?php

namespace App\SEO\Schemas;

use App\SEO\SchemaIds;
use Spatie\SchemaOrg\Schema;

class BreadcrumbSchema
{
    public static function make(array $items)
    {
        $list = [];

        foreach ($items as $index => $item) {
            $list[] = Schema::listItem()
                ->position($index + 1)
                ->name($item['name'])
                ->item($item['url']);
        }

        return Schema::breadcrumbList()
            ->setProperty('@id', SchemaIds::breadcrumb())
            ->itemListElement($list);
    }
}

