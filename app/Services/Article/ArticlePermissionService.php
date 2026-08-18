<?php

namespace App\Services\Article;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\User\User;
use App\Support\Permissions\CrudOperation;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ArticlePermissionService
{
    public function canAccessTypeCode(User $user, string $typeCode, string $guard = 'web'): bool
    {
        if ($user->hasRole('Admin', $guard)) {
            return true;
        }

        return $this->userHasPermission($user, "article-type-access.{$typeCode}", $guard);
    }

    public function canAccessTypeId(User $user, ?int $typeId, string $guard = 'web'): bool
    {
        if (! $typeId || $typeId <= 0) {
            return false;
        }

        $typeCode = ArticleType::query()
            ->withoutGlobalScopes()
            ->whereKey($typeId)
            ->value('code');

        if (! is_string($typeCode) || $typeCode === '') {
            return false;
        }

        return $this->canAccessTypeCode($user, $typeCode, $guard);
    }

    public function canUseAnyOperation(User $user, string $operation, string $guard = 'web'): bool
    {
        return $user->hasRole('Admin', $guard)
            || $this->userHasPermission($user, "article.{$operation}", $guard);
    }

    public function canUseOwnOperation(User $user, string $operation, string $guard = 'web'): bool
    {
        return $this->userHasPermission($user, "article.{$operation}-own", $guard);
    }

    public function canUseOperation(User $user, string $operation, string $guard = 'web'): bool
    {
        return $this->canUseAnyOperation($user, $operation, $guard)
            || $this->canUseOwnOperation($user, $operation, $guard);
    }

    public function canCreateType(User $user, string $typeCode, string $guard = 'web'): bool
    {
        return $this->canAccessTypeCode($user, $typeCode, $guard)
            && $this->canUseAnyOperation($user, CrudOperation::CREATE, $guard);
    }

    public function canListType(User $user, string $typeCode, string $guard = 'web'): bool
    {
        return $this->canAccessTypeCode($user, $typeCode, $guard)
            && $this->canUseOperation($user, CrudOperation::LIST, $guard);
    }

    public function accessibleTypesForMenu(?User $user, string $guard = 'web'): Collection
    {
        if (! $user) {
            return collect();
        }

        return ArticleType::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->filter(fn (ArticleType $type) => $this->canAccessTypeCode($user, $type->code, $guard));
    }

    public function canUseEntryOperation(User $user, ?Article $article, string $operation, string $guard = 'web'): bool
    {
        if (! $article || ! $this->canAccessTypeId($user, (int) $article->type_id, $guard)) {
            return false;
        }

        if ($this->canUseAnyOperation($user, $operation, $guard)) {
            return true;
        }

        return $this->canUseOwnOperation($user, $operation, $guard)
            && $this->isArticleAuthor($user, $article);
    }

    public function userHasPermission(User $user, string $permission, string $guard = 'web'): bool
    {
        try {
            return $user->hasPermissionTo($permission, $guard);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function isArticleAuthor(User $user, Article $article): bool
    {
        return $article->authors()
            ->where('users.id', $user->id)
            ->exists();
    }
}
