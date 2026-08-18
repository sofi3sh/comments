<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Http\Requests\User\RoleRequest;
use App\Support\Permissions\CrudOperation;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RoleCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RoleCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use ChecksCrudPermissions;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User\Role::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/role');
        CRUD::setEntityNameStrings(__('role.admin.title_in_singular'), __('role.admin.title_in_plural'));
        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('role.fields.name'),
            'translatable' => true,
        ]);

        $this->setupCrudPermissions('role');

        if (! is_backpack_admin()) {
            CRUD::denyAccess(CrudOperation::CREATE);
            CRUD::denyAccess(CrudOperation::UPDATE);
            CRUD::denyAccess(CrudOperation::DELETE);
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::orderBy('rank');
        CRUD::orderBy('name');

        CRUD::addColumn([
            'name'  => 'name',
            'label' => __('role.fields.name'),
        ]);

        CRUD::addColumn([
            'name' => 'rank',
            'label' => __('role.fields.rank'),
            'type' => 'number',
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(RoleRequest::class);
        CRUD::setFromDb();

        CRUD::removeField('guard_name');
        CRUD::removeField('rank');

        CRUD::addField([
            'name' => 'rank',
            'label' => __('role.fields.rank'),
            'type' => 'number',
            'default' => \App\Models\User\Role::DEFAULT_RANK,
            'attributes' => [
                'min' => 1,
                'step' => 1,
            ],
            'hint' => __('role.hints.rank'),
        ]);

        $permissionModel = config('permission.models.permission', \App\Models\User\Permission::class);
        CRUD::addField([
            'name' => 'permissions',
            'label' => __('role.fields.permissions'),
            'type' => 'select2_multiple',
            'entity' => 'permissions',
            'attribute' => 'translated_name',
            'model' => $permissionModel,
            'options' => function ($query) {
                return $query->where('guard_name', 'web')
                    ->orderBy('name')
                    ->get();
            },
            'pivot' => true,
            'allows_null' => false,
            'pivot_table' => config('permission.table_names.role_has_permissions'),
            'foreign_key' => 'role_id',
            'related_key' => 'permission_id',
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $this->crud->hasAccessOrFail(CrudOperation::CREATE);
        abort_unless(is_backpack_admin(), 403);

        request()->merge(['guard_name' => 'web']);

        $response = $this->traitStore();

        $entry = $this->crud->getCurrentEntry();

        if (request()->has('permissions')) {
            $permissions = request()->input('permissions', []);
            $entry->permissions()->sync($permissions);
        }

        return $response;
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        $this->crud->hasAccessOrFail(CrudOperation::UPDATE);
        abort_unless(is_backpack_admin(), 403);

        $response = $this->traitUpdate();

        $entry = $this->crud->getCurrentEntry();

        if (request()->has('permissions')) {
            $permissions = request()->input('permissions', []);
            $entry->permissions()->sync($permissions);
        }

        return $response;
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail(CrudOperation::DELETE);
        abort_unless(is_backpack_admin(), 403);

        $id = $this->crud->getCurrentEntryId() ?? $id;

        return $this->crud->delete($id);
    }
}
