<?php

namespace Database\Seeders\ServicePages;

use App\Support\PageRoles;

class AccessibilityPageSeeder extends AbstractServicePageSeeder
{
    protected function role(): string
    {
        return PageRoles::ACCESS;
    }

    protected function titles(): array
    {
        return [
            'uk' => 'Доступність',
            'ru' => 'Доступность',
            'en' => 'Accessibility',
        ];
    }
}