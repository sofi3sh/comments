<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Models\User\Permission;
use App\Models\User\Role;
use App\Support\Permissions\CrudOperation;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\View;

/**
 */
class PermissionCrudController extends CrudController
{
    use ChecksCrudPermissions;

    public const GUARD_WEB = 'web';

    /**
     * Configure the CrudPanel object.
     */
    public function setup(): void
    {
        CRUD::setModel(Role::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/permission');
        CRUD::setEntityNameStrings(__('permission.admin.title_in_singular'), __('permission.admin.title_in_plural'));

        CRUD::allowAccess([CrudOperation::LIST, CrudOperation::UPDATE]);
        $this->setupCrudPermissions('permission');
    }

    /**
     */
    public function index(Request $request): View
    {
        abort_unless(has_crud_permission('permission', CrudOperation::LIST), 403);

        $roles = Role::where('guard_name', self::GUARD_WEB)
            ->orderBy('rank')
            ->orderBy('name')
            ->get();
        $permissions = Permission::where('guard_name', self::GUARD_WEB)->orderBy('name')->get();

        $permissionsByEntity = $permissions->groupBy(function (Permission $p) {
            return explode('.', $p->name, 2)[0] ?? $p->name;
        });

        $entityDisplayNames = $permissionsByEntity->keys()->mapWithKeys(function (string $entityKey) {
            $key = "permission.entities.{$entityKey}";
            return [$entityKey => Lang::has($key) ? __($key) : $entityKey];
        })->all();

        $selectedRoleId = $request->input('role_id', $roles->first()?->id);
        $role = $selectedRoleId ? Role::find($selectedRoleId) : null;
        $assignedPermissionIds = $role ? $role->permissions()->pluck('id')->toArray() : [];

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['roles'] = $roles;
        $this->data['permissions'] = $permissions;
        $this->data['permissionsByEntity'] = $permissionsByEntity;
        $this->data['entityDisplayNames'] = $entityDisplayNames;
        $this->data['selectedRoleId'] = $selectedRoleId;
        $this->data['assignedPermissionIds'] = $assignedPermissionIds;

        return view('vendor.backpack.crud.permission_assign', $this->data);
    }

    public function storeAssignments(Request $request): RedirectResponse
    {
        $this->crud->hasAccessOrFail(CrudOperation::UPDATE);
        abort_unless(is_backpack_admin(), 403);

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::where('guard_name', self::GUARD_WEB)->findOrFail($request->role_id);
        $requestedIds = array_filter(array_map('intval', (array) $request->input('permissions', [])));
        $permissionIds = Permission::where('guard_name', self::GUARD_WEB)->whereIn('id', $requestedIds)->pluck('id')->toArray();

        $role->permissions()->sync($permissionIds);

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('permission.index', ['role_id' => $role->id])
            ->with('success', __('permission.assign_saved'));
    }
}
