<?php

namespace App\Http\Controllers\Traits;

use App\Models\Articles\Article;
use App\Models\User\User;
use App\Services\Article\ArticleDeleteLimitService;
use App\Services\Article\ArticlePermissionService;
use App\Support\Permissions\CrudOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Database\Eloquent\Builder;

trait ChecksArticleCrudPermissions
{
    /**
     * Налаштовує базові права CRUD для статей у Backpack.
     */
    protected function setupArticleCrudPermissions(string $guard = 'web'): void
    {
        $user = backpack_user();
        $typeCode = $this->getCurrentArticleTypeCodeForPermissions();

        if (!$user) {
            CRUD::denyAccess(CrudOperation::BASE);
            return;
        }

        CRUD::setAccessCondition(
            CrudOperation::LIST,
            $typeCode !== null && $this->canUseArticleTypeOperation($user, $typeCode, CrudOperation::LIST, $guard)
        );
        CRUD::setAccessCondition(
            CrudOperation::CREATE,
            $typeCode !== null && $this->canUseArticleTypeCreate($user, $typeCode, $guard)
        );
        CRUD::setAccessCondition(
            CrudOperation::SHOW,
            $typeCode !== null && $this->canUseArticleTypeOperation($user, $typeCode, CrudOperation::SHOW, $guard)
        );
        CRUD::setAccessCondition(
            CrudOperation::UPDATE,
            $typeCode !== null && $this->canUseArticleTypeOperation($user, $typeCode, CrudOperation::UPDATE, $guard)
        );
        CRUD::setAccessCondition(
            CrudOperation::DELETE,
            fn (?Article $entry = null): bool => $this->canUseArticleDeleteOperation($user, $entry, $guard)
        );
        CRUD::setAccessCondition(
            CrudOperation::INVALIDATE,
            fn (?Article $entry = null): bool => $this->canUseArticleEntryOperation(
                $user,
                $entry,
                CrudOperation::INVALIDATE,
                $guard
            )
        );
    }

    /**
     * Обмежує список статей до власних матеріалів, якщо немає загального доступу.
     */
    protected function applyArticleListAccessScope(string $guard = 'web'): void
    {
        $user = backpack_user();

        if (!$user || $this->canUseArticleAnyOperation($user, CrudOperation::LIST, $guard)) {
            return;
        }

        if ($this->canUseArticleOwnOperation($user, CrudOperation::LIST, $guard)) {
            CRUD::addClause('whereHas', 'authors', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id);
            });

            return;
        }

        CRUD::addClause('whereRaw', '1 = 0');
    }

    /**
     * Готує поля авторів і редакторів перед збереженням статті.
     */
    protected function prepareArticleAuthorEditorFieldsForSave(User $user, ?Article $article = null, string $guard = 'web'): void
    {
        if ($this->canManageArticleAuthors($user, $guard)) {
            return;
        }

        if ($article) {
            request()->request->remove('authors');
            request()->request->remove('editors');
            return;
        }

        request()->merge([
            'authors' => [$user->id],
        ]);
        request()->request->remove('editors');
    }

    /**
     * Прив'язує автора після створення статті користувачем без адміністративного керування авторами.
     */
    protected function syncArticleAuthorsAfterCreate(Article $article, User $user, string $guard = 'web'): void
    {
        if ($this->canManageArticleAuthors($user, $guard)) {
            return;
        }

        $article->authors()->sync([$user->id]);
    }

    /**
     * Додає користувача в редактори, якщо він редагує чужу статтю.
     */
    protected function syncArticleEditorsAfterUpdate(Article $article, User $user, string $guard = 'web'): void
    {
        if ($this->canManageArticleAuthors($user, $guard) || $this->isArticleAuthor($user, $article)) {
            return;
        }

        $article->editors()->syncWithoutDetaching([$user->id]);
    }

    /**
     * Знаходить статтю для перевірки доступу з урахуванням поточного типу.
     */
    protected function findArticleForAccessCheck(int|string|null $articleId): Article
    {
        $query = Article::query();

        if (property_exists($this, 'typeId') && isset($this->typeId)) {
            $query->where('type_id', $this->typeId);
        }

        return $query->findOrFail($articleId);
    }

    /**
     * Перевіряє доступ до конкретної статті для операцій any/own.
     */
    protected function canUseArticleEntryOperation(User $user, ?Article $article, string $operation, string $guard = 'web'): bool
    {
        if ($operation === CrudOperation::DELETE) {
            return $this->canUseArticleDeleteOperation($user, $article, $guard);
        }

        if ($article && ! $this->canAccessArticleTypeId($user, (int) $article->type_id, $guard)) {
            return false;
        }

        if (! $article) {
            $typeCode = $this->getCurrentArticleTypeCodeForPermissions();
            if ($typeCode !== null && ! $this->canAccessArticleTypeCode($user, $typeCode, $guard)) {
                return false;
            }
        }

        if ($this->canUseArticleAnyOperation($user, $operation, $guard)) {
            return true;
        }

        if (!$article) {
            return $this->canUseArticleOperation($user, $operation, $guard);
        }

        if (!$this->canUseArticleOwnOperation($user, $operation, $guard)) {
            return false;
        }

        return $this->isArticleAuthor($user, $article);
    }

    /**
     * Перевіряє право на видалення матеріалу з урахуванням повного, власного та обмеженого доступу.
     *
     * Для конкретної статті доступ надається у такому порядку:
     * - delete-any: можна видалити будь-яку статтю доступного типу;
     * - delete-own: можна видалити лише власну статтю;
     * - delete-limited: можна видалити чужу статтю, якщо для її авторів немає активного Redis-ліміту.
     *
     * Якщо статтю не передано, перевіряється лише наявність хоча б одного з цих прав
     * для поточного типу статей. Власні статті не видаляються через delete-limited.
     */
    protected function canUseArticleDeleteOperation(User $user, ?Article $article, string $guard = 'web'): bool
    {
        if ($article && ! $this->canAccessArticleTypeId($user, (int) $article->type_id, $guard)) {
            return false;
        }

        if (! $article) {
            $typeCode = $this->getCurrentArticleTypeCodeForPermissions();
            if ($typeCode !== null && ! $this->canAccessArticleTypeCode($user, $typeCode, $guard)) {
                return false;
            }

            return $this->canUseArticleAnyOperation($user, CrudOperation::DELETE, $guard)
                || $this->canUseArticleOwnOperation($user, CrudOperation::DELETE, $guard)
                || $this->canUseArticleLimitedDeleteOperation($user, $guard);
        }

        if ($this->canUseArticleAnyOperation($user, CrudOperation::DELETE, $guard)) {
            return true;
        }

        if ($this->canUseArticleOwnOperation($user, CrudOperation::DELETE, $guard) && $this->isArticleAuthor($user, $article)) {
            return true;
        }

        return $this->canUseArticleLimitedDeleteOperation($user, $guard)
            && ! $this->isArticleAuthor($user, $article)
            && app(ArticleDeleteLimitService::class)->canDelete($user, $article);
    }

    /**
     * Перевіряє, чи має користувач загальне або власне право на операцію.
     */
    protected function canUseArticleOperation(User $user, string $operation, string $guard = 'web'): bool
    {
        return $this->canUseArticleAnyOperation($user, $operation, $guard)
            || $this->canUseArticleOwnOperation($user, $operation, $guard);
    }

    /**
     * Перевіряє право на створення статей.
     */
    protected function canUseArticleCreate(User $user, string $guard = 'web'): bool
    {
        return $user->hasRole('Admin', $guard)
            || $this->userHasArticlePermission($user, 'article.create', $guard);
    }

    protected function canUseArticleTypeCreate(User $user, string $typeCode, string $guard = 'web'): bool
    {
        return $this->canAccessArticleTypeCode($user, $typeCode, $guard)
            && $this->canUseArticleCreate($user, $guard);
    }

    protected function canUseArticleTypeOperation(User $user, string $typeCode, string $operation, string $guard = 'web'): bool
    {
        return $this->canAccessArticleTypeCode($user, $typeCode, $guard)
            && $this->canUseArticleOperation($user, $operation, $guard);
    }

    protected function canAccessArticleTypeCode(User $user, string $typeCode, string $guard = 'web'): bool
    {
        return app(ArticlePermissionService::class)->canAccessTypeCode($user, $typeCode, $guard);
    }

    protected function canAccessArticleTypeId(User $user, ?int $typeId, string $guard = 'web'): bool
    {
        return app(ArticlePermissionService::class)->canAccessTypeId($user, $typeId, $guard);
    }

    /**
     * Перевіряє загальне право на операцію з будь-якими статтями.
     */
    protected function canUseArticleAnyOperation(User $user, string $operation, string $guard = 'web'): bool
    {
        return $user->hasRole('Admin', $guard)
            || $this->userHasArticlePermission($user, "article.{$operation}", $guard);
    }

    /**
     * Перевіряє право на операцію лише зі своїми статтями.
     */
    protected function canUseArticleOwnOperation(User $user, string $operation, string $guard = 'web'): bool
    {
        return $this->userHasArticlePermission($user, "article.{$operation}-own", $guard);
    }

    /**
     * Перевіряє право на обмежене видалення чужих матеріалів.
     */
    protected function canUseArticleLimitedDeleteOperation(User $user, string $guard = 'web'): bool
    {
        return $this->userHasArticlePermission($user, 'article.'.CrudOperation::DELETE_LIMITED, $guard);
    }

    /**
     * Перевіряє право на масове видалення статей.
     */
    protected function canBulkDeleteArticles(User $user, string $guard = 'web'): bool
    {
        return $user->hasRole('Admin', $guard);
    }

    /**
     * Перевіряє право змінювати авторів статті.
     */
    protected function canManageArticleAuthors(User $user, string $guard = 'web'): bool
    {
        return $user->hasRole('Admin', $guard);
    }

    /**
     * Безпечно перевіряє permission і не падає, якщо його ще немає в базі.
     */
    protected function userHasArticlePermission(User $user, string $permission, string $guard = 'web'): bool
    {
        return app(ArticlePermissionService::class)->userHasPermission($user, $permission, $guard);
    }

    /**
     * Визначає, чи є користувач автором статті.
     */
    protected function isArticleAuthor(User $user, Article $article): bool
    {
        return $article->authors()
            ->where('users.id', $user->id)
            ->exists();
    }

    private function getCurrentArticleTypeCodeForPermissions(): ?string
    {
        if (property_exists($this, 'type') && is_string($this->type) && $this->type !== '') {
            return $this->type;
        }

        return null;
    }
}
