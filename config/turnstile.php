<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    | Site key (public) — for client-side widget.
    | Secret key (private) — for server-side validation. Never expose in frontend.
    | https://developers.cloudflare.com/turnstile/
    */

    'enabled' => env('TURNSTILE_ENABLED', true),
    'site_key' => env('TURNSTILE_SITE_KEY', ''),
    'secret_key' => env('TURNSTILE_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Testing keys (for local development)
    |--------------------------------------------------------------------------
    | Use these in .env for testing: always pass = 1x00000000000000000000AA / 1x0000000000000000000000000000000AA
    */

];
