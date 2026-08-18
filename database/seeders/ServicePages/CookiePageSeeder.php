<?php

namespace Database\Seeders\ServicePages;

use App\Support\PageRoles;

class CookiePageSeeder extends AbstractServicePageSeeder
{
    protected function role(): string
    {
        return PageRoles::COOKIE;
    }

    protected function titles(): array
    {
        return [
            'uk' => 'Політика використання cookie',
            'ru' => 'Политика использования cookie',
            'en' => 'Cookie Policy',
        ];
    }
}