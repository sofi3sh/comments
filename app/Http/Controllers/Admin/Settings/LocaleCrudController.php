<?php

namespace App\Http\Controllers\Admin\Settings;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Models\Settings\Locale;
use App\Http\Requests\Settings\LocaleRequest;

class LocaleCrudController extends CrudController
{
    //use ChecksCrudPermissions;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Locale::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/locale');
        CRUD::setEntityNameStrings(__('settings.locale.title_in_singular'), __('settings.locale.title_in_plural'));

       // $this->setupCrudPermissions('seo-meta-translation');
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
            'label' => __('settings.locale.fields.name'),
        ]);
        CRUD::addColumn([
            'name'  => 'code',
            'label' => __('settings.locale.fields.code'),
        ]);
        CRUD::addColumn([
            'name'  => 'prefix',
            'label' => __('settings.locale.fields.prefix'),
        ]);
        CRUD::addColumn([
            'name'  => 'icon',
            'label' => __('settings.locale.fields.icon'),
            'type'  => 'image',
            'disk'  => 'public',
        ]);
        CRUD::addColumn([
            'name'  => 'is_default',
            'label' => __('settings.locale.fields.is_default'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return view('admin.shared.boolean-icon', ['value' => $entry->is_default])->render();
            },
        ]);
        CRUD::addColumn([
            'name'  => 'is_active',
            'label' => __('settings.locale.fields.is_active'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return view('admin.shared.boolean-icon', ['value' => $entry->is_active])->render();
            },
        ]);
    }

    /**
     * Setup the create operation
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(LocaleRequest::class);

        CRUD::addField([
            'name'  => 'name',
            'label' => __('settings.locale.fields.name'),
            'type'  => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name'  => 'code',
            'label' => __('settings.locale.fields.code'),
            'type'  => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name'  => 'prefix',
            'label' => __('settings.locale.fields.prefix'),
            'type'  => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name'  => 'is_default',
            'label' => __('settings.locale.fields.is_default'),
            'type'  => 'select_from_array',
            'options' => [
                '1' => __('settings.locale.fields.is_default_yes'),
                '0' => __('settings.locale.fields.is_default_no'),
            ],
            'default' => '0',
            'allows_null' => false,
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name'  => 'is_active',
            'label' => __('settings.locale.fields.is_active'),
            'type'  => 'select_from_array',
            'options' => [
                '1' => __('settings.locale.fields.is_active_yes'),
                '0' => __('settings.locale.fields.is_active_no'),
            ],
            'default' => '0',
            'allows_null' => false,
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        CRUD::addField([
            'name'  => 'icon',
            'label' => __('settings.locale.fields.icon'),
            'type' => 'image',
            'upload' => true,
            'disk' => 'public',
            'prefix' => 'locales',
            'crop' => true,
            'aspect_ratio' => 0,
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);


        CRUD::field('icon')->withFiles([
            'disk' => 'public',
            'path' => 'locales',
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

    /**
     * Setup the show operation
     *
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::addColumn([
            'name'  => 'created_at',
            'label' => __('settings.locale.fields.created_at'),
        ]);
        CRUD::addColumn([
            'name'  => 'updated_at',
            'label' => __('settings.locale.fields.updated_at'),
        ]);
    }
}
