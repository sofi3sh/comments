<?php

namespace App\Http\Middleware;

use App\Support\LastModifiedStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLastModified
{
    public function __construct(private readonly LastModifiedStore $lastModified)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $timestamp = $this->lastModified->get();

        if ($timestamp === null) {
            return $response;
        }

        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s \\G\\M\\T', $timestamp));
        $response->headers->set('Cache-Control', 'public, max-age=0, must-revalidate');

        if (! $request->isMethodCacheable()) {
            return $response;
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        $clientTimestamp = is_string($ifModifiedSince) ? strtotime($ifModifiedSince) : false;

        if ($clientTimestamp !== false && $clientTimestamp >= $timestamp) {
            $response->setNotModified();
        }

        return $response;
    }
}
