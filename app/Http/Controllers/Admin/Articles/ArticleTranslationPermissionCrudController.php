<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Requests\Articles\ArticleTranslationPermissionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Models\Articles\ArticleTranslationPermission;

class ArticleTranslationPermissionCrudController extends CrudController
{
    use ChecksCrudPermissions;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(ArticleTranslationPermission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/article-translation-permission');
        CRUD::setEntityNameStrings(__('article-translation-permission.admin.title_in_singular'), __('article-translation-permission.admin.title_in_plural'));

        $this->setupCrudPermissions('article-translation-permission');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'role',
            'label' => __('article-translation-permission.fields.role'),
            'type' => 'relationship',
            'attribute' => 'name',
        ]);

        CRUD::addColumn([
            'name' => 'locale',
            'label' => __('article-translation-permission.fields.locale'),
        ]);

        CRUD::addColumn([
            'name' => 'can_create',
            'label' => __('article-translation-permission.fields.can_create'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'can_update',
            'label' => __('article-translation-permission.fields.can_update'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'can_delete',
            'label' => __('article-translation-permission.fields.can_delete'),
            'type' => 'boolean',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArticleTranslationPermissionRequest::class);
        $this->getCreateOrUpdateFields();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    private function getCreateOrUpdateFields()
    {
        CRUD::addField([
            'name' => 'role_id',
            'label' => __('article-translation-permission.fields.role'),
            'type' => 'select2',
            'entity' => 'role',
            'attribute' => 'name',
            'model' => \App\Models\User\Role::class,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'locale',
            'label' => __('article-translation-permission.fields.locale'),
            'type' => 'select_from_array',
            'options' => [
                'en' => 'English',
                'ru' => 'Русский',
                'uk' => 'Українська',
            ],
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'can_create',
            'label' => __('article-translation-permission.fields.can_create'),
            'type' => 'boolean',
            'wrapper' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name' => 'can_update',
            'label' => __('article-translation-permission.fields.can_update'),
            'type' => 'boolean',
            'wrapper' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name' => 'can_delete',
            'label' => __('article-translation-permission.fields.can_delete'),
            'type' => 'boolean',
            'wrapper' => ['class' => 'form-group col-md-4'],
        ]);
    }
}

