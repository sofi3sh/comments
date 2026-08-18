<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\User\User;
use Illuminate\Support\Facades\Redis;

class ArticleDeleteLimitService
{
    private const KEY_PREFIX = 'article-delete-limited';

    private const TTL_SECONDS = 86400;

    /**
     * Перевіряє, чи може користувач видалити матеріал з урахуванням ліміту для всіх його авторів.
     */
    public function canDelete(User $actor, Article $article): bool
    {
        return $this->canDeleteForAuthorIds($actor, $this->authorIds($article));
    }

    /**
     * Перевіряє список авторів: якщо хоча б для одного автора вже є активний Redis-ключ,
     * обмежене видалення забороняється.
     *
     * @param list<int> $authorIds
     */
    public function canDeleteForAuthorIds(User $actor, array $authorIds): bool
    {
        if (empty($authorIds)) {
            return false;
        }

        foreach ($authorIds as $authorId) {
            if (Redis::exists($this->key($actor->getKey(), $authorId))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Фіксує факт обмеженого видалення матеріалу для всіх його авторів.
     */
    public function recordDelete(User $actor, Article $article): void
    {
        $this->recordDeleteForAuthorIds($actor, $this->authorIds($article));
    }

    /**
     * Створює Redis-ключі для пари "користувач, який видаляє" + "автор матеріалу"
     * з TTL 24 години.
     *
     * @param list<int> $authorIds
     */
    public function recordDeleteForAuthorIds(User $actor, array $authorIds): void
    {
        foreach ($authorIds as $authorId) {
            Redis::set($this->key($actor->getKey(), $authorId), 1, 'EX', self::TTL_SECONDS);
        }
    }

    /**
     * Повертає найбільший залишок часу ліміту серед усіх авторів матеріалу.
     */
    public function remainingSeconds(User $actor, Article $article): ?int
    {
        $remainingSeconds = collect($this->authorIds($article))
            ->map(fn (int $authorId) => Redis::ttl($this->key($actor->getKey(), $authorId)))
            ->filter(fn (int $ttl) => $ttl > 0)
            ->max();

        return $remainingSeconds === null ? null : (int) $remainingSeconds;
    }

    /**
     * Отримує унікальні ID авторів матеріалу, використовуючи вже завантажений relation,
     * якщо він доступний.
     *
     * @return list<int>
     */
    public function authorIds(Article $article): array
    {
        if ($article->relationLoaded('authors')) {
            return $article->authors
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        return $article->authors()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Формує стабільний Redis-ключ для контролю ліміту видалення.
     */
    private function key(int|string $actorId, int|string $authorId): string
    {
        return self::KEY_PREFIX.":{$actorId}:{$authorId}";
    }
}
