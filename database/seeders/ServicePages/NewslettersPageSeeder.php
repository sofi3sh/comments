<?php

namespace Database\Seeders\ServicePages;

use App\Support\PageRoles;

class NewslettersPageSeeder extends AbstractServicePageSeeder
{
    protected function role(): string
    {
        return PageRoles::NEWSLETTERS;
    }

    protected function titles(): array
    {
        return [
            'uk' => 'Розсилки',
            'ru' => 'Рассылки',
            'en' => 'Newsletters',
        ];
    }
}