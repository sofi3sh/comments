<?php

namespace App\SEO\Schemas;

use App\SEO\SchemaIds;
use Spatie\SchemaOrg\Schema;

class WebPageSchema
{
    public static function make(?string $title = null)
    {
        $schema = Schema::webPage()
            ->setProperty('@id', SchemaIds::webpage())
            ->url(url()->current())
            ->isPartOf([
                '@id' => SchemaIds::website(),
            ]);

        if ($title !== null) {
            $schema->name($title);
        }

        return $schema;
    }
}

