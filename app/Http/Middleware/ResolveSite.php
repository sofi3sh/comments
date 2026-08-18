<?php

namespace App\Http\Middleware;

use App\Models\Site\Site;
use Closure;
use Illuminate\Support\Facades\URL;

class ResolveSite
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        $site = $host ? $this->resolve($host) : null;

        // Not a site host — the API and content hosts, health checks. Those
        // requests are not site-scoped, so `currentSite` must be unbound;
        // BelongsToSite and SettingsService guard on app()->bound('currentSite').
        // (The admin panel is NOT one of these: it is served from a prefix on a
        // site domain, so currentSite is bound there and CurrentSiteScope /
        // SiteScope exempt it via is_admin_request() instead.)
        // Frontend routes cannot be reached this way: they are constrained to
        // Site::getCachedDomains() in routes/web.php.
        //
        // Clearing is explicit rather than implicit: under a worker runtime the
        // container and the UrlGenerator outlive the request, so "never bound it
        // this request" is not the same as "not bound". Without this, a request
        // to the API host following a frontend request would inherit that
        // visitor's site — and the API host does accept writes, which
        // BelongsToSite would then stamp with the wrong site_id.
        if (! $site) {
            app()->forgetInstance('currentSite');
            URL::defaults(['domain' => null]);

            return $next($request);
        }

        URL::defaults(['domain' => $host]);

        app()->instance('currentSite', $site);

        return $next($request);
    }


    private function resolve(string $host): ?Site
    {
        $parts = explode('.', $host);

        $subdomain = count($parts) >= 2 ? $parts[0] : null;

        if ($subdomain && in_array($subdomain, Site::getCachedSlugs(), true)) {
            return Site::where('slug', $subdomain)->first();
        }

        return Site::where('domain', $host)->first();
    }
}
