<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SchemaGraph extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\SEO\SchemaGraph::class;
    }
}

