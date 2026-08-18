<?php

namespace App\Http\Middleware;

use App\Facades\SchemaGraph;
use App\SEO\Schemas\OrganizationSchema;
use App\SEO\Schemas\WebPageSchema;
use App\SEO\Schemas\WebSiteSchema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectGlobalSchemas
{
    public function handle(Request $request, Closure $next): Response
    {
        SchemaGraph::add(OrganizationSchema::make());
        SchemaGraph::add(WebSiteSchema::make());
        SchemaGraph::add(WebPageSchema::make());

        return $next($request);
    }
}

