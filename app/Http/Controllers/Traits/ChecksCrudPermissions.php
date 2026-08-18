<?php

namespace App\Http\Controllers\Traits;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait ChecksCrudPermissions
{

    protected function setupCrudPermissions(string $permissionPrefix, string $adminRoleName = 'Admin', string $guard = 'web'): void
    {
        $user = backpack_user();

        if (!$user) {
            return;
        }

        $isAdmin = $user->hasRole($adminRoleName, $guard);
        $operations = ['list', 'create', 'update', 'delete', 'show'];
        $deniedOperations = [];

        foreach ($operations as $operation) {
            $permission = "{$permissionPrefix}.{$operation}";
            
            if (!$isAdmin && !$user->hasPermissionTo($permission, $guard)) {
                $deniedOperations[] = $operation;
            }
        }

        foreach ($deniedOperations as $operation) {
            CRUD::denyAccess($operation);
        }
    }


    protected function getDeniedOperations(string $permissionPrefix, string $adminRoleName = 'Admin', string $guard = 'web'): array
    {
        $user = backpack_user();

        if (!$user) {
            return ['list', 'create', 'update', 'delete', 'show'];
        }

        $isAdmin = $user->hasRole($adminRoleName, $guard);
        $operations = ['list', 'create', 'update', 'delete', 'show'];
        $deniedOperations = [];

        foreach ($operations as $operation) {
            $permission = "{$permissionPrefix}.{$operation}";
            
            if (!$isAdmin && !$user->hasPermissionTo($permission, $guard)) {
                $deniedOperations[] = $operation;
            }
        }

        return $deniedOperations;
    }

    protected function hasPermissionForOperation(string $modelName, string $operation, string $adminRoleName = 'Admin', string $guard = 'web'): bool
    {
        $user = backpack_user();

        if (!$user) {
            return false;
        }

        $isAdmin = $user->hasRole($adminRoleName, $guard);

        if ($isAdmin) {
            return true;
        }

        $permission = "{$modelName}.{$operation}";

        return $user->hasPermissionTo($permission, $guard);
    }
}

