<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Http\Controllers\Traits\ValidatesPhoneNumber;
use App\Http\Requests\User\AdminCreateUserRequest;
use App\Http\Requests\User\AdminUpdateUserRequest;
use App\Models\User\User;
use App\Services\User\UserRoleAccessService;
use App\Services\User\UserAvatarUploader;
use App\Support\Permissions\CrudOperation;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use ChecksCrudPermissions;
    use ValidatesPhoneNumber;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings(__('user.admin.title_in_singular'), __('user.admin.title_in_plural'));

        $this->setupCrudPermissions('user');
        CRUD::setAccessCondition(CrudOperation::UPDATE, fn (?User $entry = null): bool => has_crud_permission('user', CrudOperation::UPDATE)
            && ($entry === null || (! $entry->trashed() && $this->roleAccess()->canManageTargetUser($entry))));
        CRUD::setAccessCondition(CrudOperation::DELETE, fn (?User $entry = null): bool => has_crud_permission('user', CrudOperation::BLOCK)
            && ($entry === null || $this->canBlockTargetUser($entry)));
        CRUD::setAccessCondition(CrudOperation::SHOW, fn (?User $entry = null): bool => has_crud_permission('user', CrudOperation::SHOW)
            && ($entry === null || ! $entry->trashed()));
    }


    protected function setupListOperation()
    {
        $this->crud->query->withTrashed();
        CRUD::removeButton('delete');

        CRUD::addColumn([
            'name' => 'avatar',
            'label' => __('user.fields.avatar'),
            'type' => 'image',
            'height' => '50px',
            'width' => '50px',
            'disk' => 'public',
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('user.fields.name'),
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'surname',
            'label' => __('user.fields.surname'),
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'email',
            'label' => __('user.fields.email'),
            'type' => 'email',
        ]);
        CRUD::addColumn([
            'name' => 'phone',
            'label' => __('user.fields.phone'),
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'blocked_status',
            'label' => __('user.fields.status'),
            'type' => 'closure',
            'function' => fn (User $entry) => $entry->trashed()
                ? __('user.statuses.blocked')
                : __('user.statuses.active'),
        ]);

        if (has_crud_permission('user', CrudOperation::BLOCK)) {
            CRUD::addButtonFromView('line', 'user_block_toggle', 'user_block_toggle', 'end');
        }
    }


    protected function setupCreateOperation()
    {
        CRUD::setValidation(AdminCreateUserRequest::class);
       
        CRUD::field('avatar')
            ->label(__('user.fields.avatar'))
            ->type('upload')
            ->withFiles(['uploader' => UserAvatarUploader::class])
            ->wrapper(['class' => 'form-group col-md-4']);

        CRUD::addField([
            'name' => 'name',
            'label' => __('user.fields.name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name' => 'surname',
            'label' => __('user.fields.surname'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name' => 'email',
            'label' => __('user.fields.email'),
            'type' => 'email',
            'attributes' => [
                'placeholder' => 'name@example.com',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name' => 'facebook_url',
            'label' => __('user.fields.facebook_url'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name' => 'linkedin_url',
            'label' => __('user.fields.linkedin_url'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name' => 'twitter_url',
            'label' => __('user.fields.twitter_url'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name' => 'phone',
            'label' => __('user.fields.phone'),
            'type' => 'text',
            'attributes' => [
                'inputmode' => 'tel',
                'pattern' => '[0-9+\\s\\-]*',
                'maxlength' => 20,
                'placeholder' => '0970000000 або +380970000000',
                'title' => __('user.validation.phone_format'),
            ],
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name' => 'password',
            'label' => __('user.fields.password'),
            'type' => 'password',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'password_confirmation',
            'label' => __('user.fields.password_confirmation'),
            'type' => 'password',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        if ($this->roleAccess()->canUpdateUserRoles()) {
            CRUD::addField([
                'name' => 'roles',
                'label' => __('user.fields.roles'),
                'type' => 'select2_multiple',
                'entity' => 'roles',
                'attribute' => 'name',
                'model' => config('permission.models.role', \Spatie\Permission\Models\Role::class),
                'options' => fn ($query) => $this->roleAccess()->applyAssignableRolesScope($query)->get(),
                'pivot' => true,
                'allows_null' => false,
            ]);
        }
    }


    protected function setupUpdateOperation()
    {
        $this->roleAccess()->authorizeCanManageUser($this->findTargetUserForCurrentRoute());

        $this->setupCreateOperation();

        CRUD::setValidation(AdminUpdateUserRequest::class);

        CRUD::modifyField('password', [
            'value' => '',
        ]);

        CRUD::modifyField('password_confirmation', [
            'value' => '',
        ]);
    }


    public function store()
    {
        $this->crud->hasAccessOrFail(CrudOperation::CREATE);

        $request = $this->crud->getRequest();
        $this->roleAccess()->authorizeSubmittedRoleChanges($request);

        if ($request->has('phone') && $request->filled('phone')) {
            $request->merge(['phone' => $this->normalizePhoneNumber($request->input('phone'))]);
        }

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        $saveData = $this->crud->getStrippedSaveRequest($request);
        $baseName = trim((string) ($saveData['name'] ?? ''));
        $baseSurname = isset($saveData['surname']) ? trim((string) $saveData['surname']) : null;
        unset($saveData['name'], $saveData['surname']);

        $item = $this->crud->model->newInstance();
        $item->fill($saveData);
        $item->setRawAttributes(array_merge($item->getAttributes(), [
            'name' => $baseName,
            'surname' => $baseSurname,
        ]));
        $item->save();

        $this->data['entry'] = $this->crud->entry = $item;

        if ($this->roleAccess()->canUpdateUserRoles() && request()->has('roles')) {
            $this->roleAccess()->syncAssignableRoles($item, request()->input('roles', []));
        }

        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }


    public function update()
    {
        $this->crud->hasAccessOrFail(CrudOperation::UPDATE);

        $request = $this->crud->getRequest();
        $this->roleAccess()->authorizeCanManageUser($this->findTargetUserForRequest($request));
        $this->roleAccess()->authorizeSubmittedRoleChanges($request);

        if (!$request->filled('password')) {
            $request->request->remove('password');
            $request->request->remove('password_confirmation');
        }

        if ($request->has('phone')) {
            $phone = $request->input('phone');
            if (!empty($phone)) {
                $request->merge(['phone' => $this->normalizePhoneNumber($phone)]);
            } else {
                $request->merge(['phone' => null]);
            }
        }

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        $saveData = $this->crud->getStrippedSaveRequest($request);
        $baseName = trim((string) ($saveData['name'] ?? ''));
        $baseSurname = isset($saveData['surname']) ? trim((string) $saveData['surname']) : null;
        unset($saveData['name'], $saveData['surname']);

        $item = $this->crud->update(
            $request->get($this->crud->model->getKeyName()),
            $saveData
        );
        $item->setRawAttributes(array_merge($item->getAttributes(), [
            'name' => $baseName,
            'surname' => $baseSurname,
        ]));
        $item->save();

        $this->data['entry'] = $this->crud->entry = $item;

        if ($this->roleAccess()->canUpdateUserRoles() && request()->has('roles')) {
            $this->roleAccess()->syncAssignableRoles($item, request()->input('roles', []));
        }

        \Alert::success(trans('backpack::crud.update_success'))->flash();

        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    public function destroy($id)
    {
        return $this->block($id);
    }

    public function block($id)
    {
        abort_unless(has_crud_permission('user', CrudOperation::BLOCK), 403);

        $user = $this->findUserWithTrashed($id);
        $this->authorizeCanBlockUser($user);

        if (! $user->trashed()) {
            $user->delete();
        }

        Alert::success(__('user.messages.blocked'))->flash();

        return redirect()->back();
    }

    public function restore($id)
    {
        abort_unless(has_crud_permission('user', CrudOperation::BLOCK), 403);

        $user = $this->findUserWithTrashed($id);
        $this->authorizeCanBlockUser($user);

        if ($user->trashed()) {
            $user->restore();
        }

        Alert::success(__('user.messages.unblocked'))->flash();

        return redirect()->back();
    }

    private function findUserWithTrashed($id): User
    {
        $id = $this->crud->getCurrentEntryId() ?? $id;

        return User::withTrashed()->findOrFail($id);
    }

    private function authorizeCanBlockUser(User $user): void
    {
        abort_unless($this->canBlockTargetUser($user), 403);
    }

    private function canBlockTargetUser(User $user): bool
    {
        $actor = backpack_user();

        return $actor
            && (int) $actor->getKey() !== (int) $user->getKey()
            && $this->roleAccess()->canManageTargetUser($user);
    }

    private function findTargetUserForCurrentRoute(): User
    {
        $id = request()->route('user') ?? request()->route('id');

        if ($id instanceof User) {
            return $id;
        }

        return $this->crud->getEntry($id);
    }

    private function findTargetUserForRequest($request): User
    {
        $id = $request->get($this->crud->model->getKeyName())
            ?? request()->route('user')
            ?? request()->route('id');

        if ($id instanceof User) {
            return $id;
        }

        return $this->crud->getEntry($id);
    }

    private function roleAccess(): UserRoleAccessService
    {
        return app(UserRoleAccessService::class);
    }
}
