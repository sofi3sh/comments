<?php

namespace App\Http\Middleware;

use Closure;

class DetectLocaleOld
{
    public function handle($request, Closure $next)
    {
        // OLD SYSTEM RULE:
        // - no locale = ru
        // - ua prefix = uk (legacy)

        app()->setLocale($request->segment(1) === 'ua' ? 'uk' : 'ru');

        return $next($request);
    }
}
