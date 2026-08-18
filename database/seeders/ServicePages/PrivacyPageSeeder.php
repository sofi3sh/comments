<?php

namespace Database\Seeders\ServicePages;

use App\Support\PageRoles;

class PrivacyPageSeeder extends AbstractServicePageSeeder
{
    protected function role(): string
    {
        return PageRoles::PRIVACY;
    }

    protected function titles(): array
    {
        return [
            'uk' => 'Політика конфіденційності',
            'ru' => 'Политика конфиденциальности',
            'en' => 'Privacy Policy',
        ];
    }
}