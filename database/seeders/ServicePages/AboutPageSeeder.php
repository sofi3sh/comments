<?php

namespace Database\Seeders\ServicePages;

use App\Support\PageRoles;

class AboutPageSeeder extends AbstractServicePageSeeder
{
    protected function role(): string
    {
        return PageRoles::ABOUT;
    }

    protected function titles(): array
    {
        return [
            'uk' => 'Про нас',
            'ru' => 'О нас',
            'en' => 'About Us',
        ];
    }
}