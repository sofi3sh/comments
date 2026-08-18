<?php

namespace App\SEO;

use Spatie\SchemaOrg\Graph;

class SchemaGraph
{
    protected Graph $graph;

    public function __construct()
    {
        $this->graph = new Graph();
    }

    public function add(mixed $schema): void
    {
        $this->graph->add($schema);
    }

    public function graph(): Graph
    {
        return $this->graph;
    }

    public function toScript(): string
    {
        return $this->graph->toScript();
    }

    public function reset(): void
    {
        $this->graph = new Graph();
    }
}

