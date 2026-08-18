<?php

namespace Database\Seeders\ServicePages;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleMeta;
use App\Models\Articles\ArticleType;
use App\Models\Site\Site;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

abstract class AbstractServicePageSeeder extends Seeder
{
    protected const LOCALES = ['uk', 'ru', 'en'];

    abstract protected function role(): string;

    abstract protected function titles(): array;

    public function run(): void
    {
        $typeId = ArticleType::getTypeId(ArticleType::PAGE);

        if ($typeId === null) {
            return;
        }

        if ($this->servicePageExists($typeId)) {
            return;
        }

        $article = Article::query()->create([
            'type_id' => $typeId,
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $this->attachSites($article);
        $this->attachAdminAuthor($article);
        $this->createTranslations($article);
        $this->createRoleMeta($article);
    }

    private function servicePageExists(int $typeId): bool
    {
        return Article::query()
            ->where('type_id', $typeId)
            ->whereHas('meta', fn ($query) => $query
                ->where('field', 'page_role')
                ->where('value', $this->role()))
            ->exists();
    }

    private function attachSites(Article $article): void
    {
        $siteIds = Site::query()->pluck('id')->all();

        if ($siteIds !== []) {
            $article->sites()->syncWithoutDetaching($siteIds);
        }
    }

    private function attachAdminAuthor(Article $article): void
    {
        $admin = User::query()->where('email', 'admin@admin.com')->first();

        if ($admin !== null) {
            $article->authors()->syncWithoutDetaching([$admin->getKey()]);
        }
    }

    private function createTranslations(Article $article): void
    {
        foreach (self::LOCALES as $locale) {
            $title = $this->titles()[$locale];
            $text = 'стартовый текст ' . mb_strtolower($title, 'UTF-8');

            $translation = $article->translateOrNew($locale);
            $translation->title = $title;
            $translation->slug = Str::slug($title) ?: $this->role();
            $translation->content = $this->makeEditorContent($text);
        }

        $article->save();
    }

    private function makeEditorContent(string $text): string
    {
        $content = [
            'time' => $this->editorTimestamp(),
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => $text,
                    ],
                ],
            ],
            'version' => '2.30.8',
        ];

        return json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ?: '{"time":0,"blocks":[],"version":"2.30.8"}';
    }

    private function editorTimestamp(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    private function createRoleMeta(Article $article): void
    {
        foreach (self::LOCALES as $locale) {
            ArticleMeta::query()->updateOrCreate(
                [
                    'article_id' => $article->getKey(),
                    'locale' => $locale,
                    'field' => 'page_role',
                ],
                [
                    'value' => $this->role(),
                ]
            );
        }
    }
}
