<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Translate\ArticleTypeTranslation;

class ArticleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'news'],
            ['code' => 'article'],
            ['code' => 'interview'],
            ['code' => 'person'],
            ['code' => 'company'],
            ['code' => 'opinion'],
            ['code' => 'video'],
            ['code' => 'page'],
        ];

        $translations = [
            'news' => [
                'en' => 'News',
                'ru' => 'Новости',
                'uk' => 'Новини',
            ],
            'article' => [
                'en' => 'Articles',
                'ru' => 'Статьи',
                'uk' => 'Статті',
            ],
            'interview' => [
                'en' => 'Interview',
                'ru' => 'Интервью',
                'uk' => 'Інтерв\'ю',
            ],
            'person' => [
                'en' => 'Persons',
                'ru' => 'Персоны',
                'uk' => 'Персони',
            ],
            'company' => [
                'en' => 'Companies',
                'ru' => 'Компании',
                'uk' => 'Компанії',
            ],
            'opinion' => [
                'en' => 'Opinions',
                'ru' => 'Мнения',
                'uk' => 'Думки',
            ],
            'video' => [
                'en' => 'Video',
                'ru' => 'Видео',
                'uk' => 'Відео',
            ],
            'page' => [
                'en' => 'Page',
                'ru' => 'Страница',
                'uk' => 'Сторінка',
            ],
        ];

        foreach ($types as $typeData) {
            $type = ArticleType::updateOrCreate(
                ['code' => $typeData['code']],
                ['is_active' => true]
            );

            foreach (['en', 'ru', 'uk'] as $locale) {
                ArticleTypeTranslation::updateOrCreate(
                    [
                        'article_type_id' => $type->id,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $translations[$typeData['code']][$locale],
                    ]
                );
            }
        }
    }
}

