<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackpackEmailVerified
{

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('backpack.base.enforce_email_verification', false)) {
            return $next($request);
        }

        if ($request->routeIs('verification.*')) {
            return $next($request);
        }

        $user = backpack_user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
