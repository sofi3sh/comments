<?php

namespace App\Services\User;

use App\Models\User\User;
use App\Support\Permissions\CrudOperation;

class UserRoleAccessService
{
    protected string $guard;

    /**
     * Зберігає guard, у межах якого перевіряються ролі та дозволи користувачів.
     */
    public function __construct()
    {
        $this->guard = config('permission.defaults.guard', 'web');
    }

    /**
     * Перевіряє, чи може поточний користувач змінювати ролі інших користувачів.
     */
    public function canUpdateUserRoles(): bool
    {
        return has_crud_permission('user', CrudOperation::UPDATE_ROLES);
    }

    /**
     * Перериває запит, якщо поточний користувач не може керувати вказаним користувачем.
     */
    public function authorizeCanManageUser(User $user): void
    {
        abort_unless($this->canManageTargetUser($user), 403);
    }

    /**
     * Визначає, чи може поточний користувач керувати цільовим користувачем за ієрархією ролей.
     */
    public function canManageTargetUser(User $user): bool
    {
        if ($this->currentUserCanAssignAnyRole()) {
            return true;
        }

        $currentRank = $this->currentUserRank();
        if ($currentRank === null) {
            return false;
        }

        $targetRank = $this->userRank($user);

        if ($targetRank === null) {
            return ! $this->userHasGuardRoles($user);
        }

        return $targetRank > $currentRank;
    }

    /**
     * Забороняє пряме надсилання ролей у request, якщо поточний користувач не має права їх змінювати.
     */
    public function authorizeSubmittedRoleChanges($request): void
    {
        if (! $request->has('roles')) {
            return;
        }

        abort_unless($this->canUpdateUserRoles(), 403);

        $submittedRoleIds = $this->normalizeRoleIds($request->input('roles', []));
        abort_unless($this->canAssignRoleIds($submittedRoleIds), 403);
    }

    /**
     * Синхронізує тільки ті ролі, які поточний користувач має право призначати.
     */
    public function syncAssignableRoles(User $user, mixed $submittedRoleIds): void
    {
        $roleIds = $this->normalizeRoleIds($submittedRoleIds);

        if (! $this->currentUserCanAssignAnyRole()) {
            $assignableRoleIds = $this->assignableRoleIds();
            $protectedRoleIds = $user->roles()
                ->whereNotIn('roles.id', $assignableRoleIds)
                ->pluck('roles.id')
                ->all();

            $roleIds = array_values(array_unique(array_merge($protectedRoleIds, $roleIds)));
        }

        $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);
        $roles = $roleModel::query()
            ->where('guard_name', $this->guard)
            ->whereIn('id', $roleIds)
            ->pluck('name')
            ->toArray();

        $user->syncRoles($roles);
    }

    /**
     * Обмежує запит ролей списком, доступним для призначення поточному користувачу.
     */
    public function applyAssignableRolesScope($query)
    {
        $query->where('guard_name', $this->guard)
            ->orderBy('rank')
            ->orderBy('name');

        if ($this->currentUserCanAssignAnyRole()) {
            return $query;
        }

        $currentLevel = $this->currentUserRank();

        return $currentLevel === null
            ? $query->whereRaw('1 = 0')
            : $query
                ->whereNotNull('rank')
                ->where('rank', '>', $currentLevel);
    }

    /**
     * Перевіряє, чи всі передані ролі входять до списку ролей, доступних для призначення.
     */
    public function canAssignRoleIds(array $roleIds): bool
    {
        if (empty($roleIds) || $this->currentUserCanAssignAnyRole()) {
            return true;
        }

        return empty(array_diff($roleIds, $this->assignableRoleIds()));
    }

    /**
     * Повертає ID ролей, які поточний користувач може призначати іншим користувачам.
     */
    public function assignableRoleIds(): array
    {
        $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        return $this->applyAssignableRolesScope($roleModel::query())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Визначає, чи поточний користувач може призначати будь-яку роль без ієрархічних обмежень.
     */
    public function currentUserCanAssignAnyRole(): bool
    {
        return is_backpack_admin(guard: $this->guard);
    }

    /**
     * Повертає ранг ролі поточного користувача.
     */
    public function currentUserRank(): ?int
    {
        $user = backpack_user();

        return $user instanceof User ? $this->userRank($user) : null;
    }

    /**
     * Повертає найвищий ранг ролі користувача, де менше число означає вищу роль.
     */
    public function userRank(User $user): ?int
    {
        $level = $user->roles()
            ->where('guard_name', $this->guard)
            ->whereNotNull('rank')
            ->min('rank');

        return $level === null ? null : (int) $level;
    }

    /**
     * Перевіряє, чи має користувач хоча б одну роль у поточному guard.
     */
    public function userHasGuardRoles(User $user): bool
    {
        return $user->roles()
            ->where('guard_name', $this->guard)
            ->exists();
    }

    /**
     * Нормалізує отримані ID ролей до унікального масиву додатних цілих чисел.
     */
    public function normalizeRoleIds(mixed $roleIds): array
    {
        if (! is_array($roleIds)) {
            $roleIds = [$roleIds];
        }

        return collect($roleIds)
            ->filter(fn ($roleId) => $roleId !== null && $roleId !== '')
            ->map(fn ($roleId) => (int) $roleId)
            ->filter(fn (int $roleId) => $roleId > 0)
            ->unique()
            ->values()
            ->all();
    }
}
