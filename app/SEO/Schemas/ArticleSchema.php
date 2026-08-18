<?php

namespace App\SEO\Schemas;

use App\SEO\SchemaIds;
use Spatie\SchemaOrg\Schema;

class ArticleSchema
{
    public static function make(object $article)
    {
        return Schema::article()
            ->setProperty('@id', SchemaIds::article())
            ->headline($article->title)
            ->datePublished($article->created_at)
            ->dateModified($article->updated_at)
            ->mainEntityOfPage([
                '@id' => SchemaIds::webpage(),
            ])
            ->publisher([
                '@id' => SchemaIds::organization(),
            ]);
    }
}

