<?php

namespace App\Repositories;

use App\Models\Articles\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagRepository
{
    public function getHomepageTags(
        string $locale,
        int $limit = 5
    ): Collection {

        return Tag::query()

            ->where('homepage', true)
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale)
                    ->whereNotNull('title')
                    ->where('title', '!=', '')
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '');
            })

            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale)
                        ->select([
                            'tag_id',
                            'locale',
                            'title',
                            'slug',
                        ]);
                }
            ])

            ->latest()
            ->take($limit)
            ->get();
    }


    public function findTagBySlug(
        string $slug,
        string $locale
    ): ?Tag
    {
        return Tag::query()
            ->where('homepage', true)
            ->whereTranslation('slug', $slug, $locale)
            ->first();
    }


    public function findTagBySlugAnyLocale(string $slug): ?Tag
    {
        return Tag::query()
            ->where('homepage', true)
            ->whereHas('translations', fn ($q) =>
            $q->where('slug', $slug)
            )
            ->first();
    }
}