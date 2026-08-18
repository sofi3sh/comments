<?php

namespace App\Services\Article;

use App\Models\Articles\ArticleFieldConfiguration;
use Illuminate\Support\Collection;

final class ArticleFieldConfigurationResolver
{
    /**
     * Формує кінцевий набір налаштувань полів для вказаного типу статті.
     *
     * Спочатку завантажуються загальні налаштування без `article_type_id`.
     * Якщо для поля існує налаштування конкретного типу, воно замінює загальне.
     * Результат повертається як колекція, де ключем є назва поля.
     *
     * @return Collection<string, ArticleFieldConfiguration>
     */
    public function forType(?int $typeId): Collection
    {
        $configs = ArticleFieldConfiguration::query()
            ->where(function ($query) use ($typeId): void {
                $query->whereNull('article_type_id');

                if ($typeId !== null && $typeId > 0) {
                    $query->orWhere('article_type_id', $typeId);
                }
            })
            ->orderBy('id')
            ->get();

        return $configs->reduce(function (Collection $effective, ArticleFieldConfiguration $config): Collection {
            $current = $effective->get($config->field_name);

            if ($current === null || $config->article_type_id !== null) {
                $effective->put($config->field_name, $config);
            }

            return $effective;
        }, collect());
    }
}
