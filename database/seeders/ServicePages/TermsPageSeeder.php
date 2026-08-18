<?php

namespace Database\Seeders\ServicePages;

use App\Support\PageRoles;

class TermsPageSeeder extends AbstractServicePageSeeder
{
    protected function role(): string
    {
        return PageRoles::TERMS;
    }

    protected function titles(): array
    {
        return [
            'uk' => 'Умови використання',
            'ru' => 'Условия использования',
            'en' => 'Terms of Use',
        ];
    }
}