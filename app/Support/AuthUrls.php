<?php

namespace App\Support;

class AuthUrls
{
    /**
     * The admin panel is served from the `admin` prefix on the current host —
     * backpack_url() applies config('backpack.base.route_prefix') for us.
     */
    public static function admin(string $path = ''): string
    {
        return backpack_url($path);
    }


    public static function frontend(string $path = '', array $query = []): string
    {
        $url = self::absolute(config('app.url'), $path);

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }


    public static function frontendAuth(string $mode): string
    {
        return self::frontend('', ['auth' => $mode]);
    }


    private static function absolute(string $baseUrl, string $path = ''): string
    {
        if (! preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = request()->getScheme().'://'.$baseUrl;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }
}
