<?php

namespace App\SEO;

class SchemaIds
{
    public static function organization(): string
    {
        return config('app.url').'#organization';
    }

    public static function website(): string
    {
        return config('app.url').'#website';
    }

    public static function webpage(): string
    {
        return url()->current().'#webpage';
    }

    public static function article(): string
    {
        return url()->current().'#article';
    }

    public static function breadcrumb(): string
    {
        return url()->current().'#breadcrumb';
    }
}

