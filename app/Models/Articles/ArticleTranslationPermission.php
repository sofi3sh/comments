<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class ArticleTranslationPermission extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'role_id',
        'locale',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(\App\Models\User\Role::class);
    }

    /**
     * Check if user can perform action on locale
     */
    public static function canUserPerformAction(int $userId, string $locale, string $action): bool
    {
        $user = \App\Models\User\User::find($userId);
        if (!$user) {
            return false;
        }

        // If no permissions configured at all, allow access (backward compatibility)
        if (!static::exists()) {
            return true;
        }

        $roles = $user->roles->pluck('id');
        if ($roles->isEmpty()) {
            return false;
        }

        $permission = static::whereIn('role_id', $roles)
            ->where('locale', $locale)
            ->first();

        if (!$permission) {
            return false;
        }

        return match($action) {
            'create' => $permission->can_create,
            'update' => $permission->can_update,
            'delete' => $permission->can_delete,
            default => false,
        };
    }
}

