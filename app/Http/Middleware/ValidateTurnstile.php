<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ValidateTurnstile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('turnstile.enabled')) {
            return $next($request);
        }

        $secretKey = config('turnstile.secret_key');

        if (empty($secretKey)) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response');

        if (empty($token)) {
            return $this->reject($request, 'authorization.turnstile_missing');
        }

        $remoteip = $request->header('CF-Connecting-IP')
            ?? $request->header('X-Forwarded-For')
            ?? $request->ip();

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $remoteip,
        ]);

        $result = $response->json();

        if (empty($result['success'])) {
            return $this->reject($request, 'authorization.turnstile_failed');
        }

        return $next($request);
    }

    /**
     * Return a Turnstile validation failure as JSON
     * or reopen the relevant auth popup.
     */
    protected function reject(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __($message)], 422);
        }

        $mode = $request->routeIs('frontend.auth.register') ? 'registration' : 'login';

        return redirect()->back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->with('auth_popup_mode', $mode)
            ->withErrors(['cf-turnstile-response' => __($message)]);
    }
}
