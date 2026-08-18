<?php

namespace App\Support;

class PageRoles
{
    public const TERMS   = 'terms';
    public const PRIVACY = 'privacy';
    public const ABOUT   = 'about';
    public const COOKIE  = 'cookie';
    public const ACCESS  = 'accessibility';
    public const NEWSLETTERS = 'newsletters';

    public static function all(): array
    {
        return [
            self::TERMS,
            self::PRIVACY,
            self::ABOUT,
            self::COOKIE,
            self::ACCESS,
            self::NEWSLETTERS
        ];
    }
}