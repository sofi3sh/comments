<?php

namespace Database\Seeders;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\Site\Site;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class DefaultPrivacyPolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        $typeId = ArticleType::query()->where('code', 'page')->value('id');

        if ($typeId === null) {
            return;
        }

        $site = Site::query()->find(1);

        if ($site === null) {
            return;
        }

        $admin = User::query()->where('email', 'admin@admin.com')->first();

        $article = Article::query()
            ->where('type_id', $typeId)
            ->whereHas('sites', function ($q) use ($site) {
                $q->whereKey($site->getKey());
            })
            ->whereHas('translations', function ($q) {
                $q->where('locale', 'uk')
                    ->where('title', 'Політика конфіденційності');
            })
            ->first();

        if ($article === null) {
            $article = Article::query()->create([
                'type_id' => $typeId,
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => now(),
                'views' => 0,
            ]);
        }

        $article->sites()->syncWithoutDetaching([$site->getKey()]);

        if ($admin !== null) {
            $article->authors()->syncWithoutDetaching([$admin->getKey()]);
        }

        $titlesByLocale = [
            'uk' => 'Політика конфіденційності',
            'ru' => 'Политика конфиденциальности',
            'en' => 'Privacy Policy',
        ];

        foreach (['uk', 'ru', 'en'] as $locale) {
            $translation = $article->translateOrNew($locale);
            $translation->title = $titlesByLocale[$locale];
        }

        $article->save();
    }
}

