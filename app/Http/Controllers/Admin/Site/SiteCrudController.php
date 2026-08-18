<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Requests\SiteRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;

use \App\Models\Site\Site;
class SiteCrudController extends CrudController
{
    use ChecksCrudPermissions;
    
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Site::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/site');
        CRUD::setEntityNameStrings(__('site.admin.title_in_singular'), __('site.admin.title_in_plural'));   

        $this->setupCrudPermissions('site');
    }

    /*
    |--------------------------------------------------------------------------
    | SETUP OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Setup the list operation
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'  => 'name',
            'label' => __('site.fields.name'),
        ]);
        CRUD::addColumn([
            'name'  => 'slug',
            'label' => __('site.fields.slug'),
        ]);
        CRUD::addColumn([
            'name'  => 'domain',
            'label' => __('site.fields.domain'),
        ]);
        CRUD::addColumn([
            'name'  => 'active',
            'label' => __('site.fields.active'),
        ]);
        CRUD::addColumn([
            'name'  => 'created_at',
            'label' => __('site.fields.created_at'),
        ]);
        CRUD::addColumn([
            'name'  => 'updated_at',
            'label' => __('site.fields.updated_at'),
        ]);
    }

    /**
     * Setup the create operation
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SiteRequest::class);

        CRUD::addField([
            'name' => 'name',
            'label' => __('site.fields.name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-10',
            ],
        ]);
        CRUD::addField([
            'name' => 'active',
            'label' => __('site.fields.active'),
            'type' => 'checkbox',
            'wrapper' => [
                'class' => 'form-group col-md-2',
            ],
        ]);
        CRUD::addField([
            'name' => 'slug',
            'label' => __('site.fields.slug'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'domain',
            'label' => __('site.fields.domain'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'color_primary',
            'label' => __('site.fields.color_primary'),
            'type' => 'color',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'color_secondary',
            'label' => __('site.fields.color_secondary'),
            'type' => 'color',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'logo',
            'label' => __('site.fields.logo'),
            'type' => 'image',
            'upload' => true,
            'disk' => 'public',
            'prefix' => 'uploads/sites',
            'crop' => true,
            'aspect_ratio' => 0,
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::field('logo')->withFiles([
            'disk' => 'public',
            'path' => 'uploads/sites',
        ]);
    }

    /**
     * Setup the update operation
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
