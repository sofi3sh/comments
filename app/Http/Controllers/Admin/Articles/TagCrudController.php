<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Traits\CrudTrait;
use App\Http\Requests\Articles\TagRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Http\Controllers\Traits\HasLocaleTab;
use App\Http\Controllers\Traits\HasSeoTab;
use \App\Models\Articles\Tag;
use \App\Models\Articles\Translate\TagTranslation;

class TagCrudController extends CrudController
{
    use ChecksCrudPermissions;
    use HasLocaleTab;
    use HasSeoTab;
    use CrudTrait;

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Tag::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/tag');
        CRUD::setEntityNameStrings(__('tag.admin.title_in_singular'), __('tag.admin.title_in_plural'));

        $this->setupCrudPermissions('tag');
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
        $this->getListFields();
    }

    /**
     * Setup the create operation
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(TagRequest::class);

        CRUD::addField([
            'name'  => 'homepage',
            'label' => __('tag.fields.homepage'),
            'type' => 'checkbox',
            'wrapper' => ['class' => 'form-group col-md-6 mt-4'],
            'tab' => __('admin.tabs.general'),
        ]);

        $this->createLocalesTabsWrapper(TagTranslation::class);
    
        $this->createSeoTabs(Tag::class);
    }

    /**
     * Setup the update operation
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(TagRequest::class);

        CRUD::addField([
            'name'  => 'homepage',
            'label' => __('tag.fields.homepage'),
            'type' => 'checkbox',
            'wrapper' => ['class' => 'form-group col-md-6 mt-4'],
            'tab' => __('admin.tabs.general'),
        ]);


        $tagId = request()->route('id') ?? request()->route('tag');
        
        $this->createLocalesTabsWrapper(TagTranslation::class, $tagId, 'tag_id');
        
        $this->createSeoTabs(Tag::class, $tagId);
    }

    /**
     * Setup the show operation
     *
     * @return void
    */
    protected function setupShowOperation()
    {
        $this->getListFields();

        CRUD::addColumn([
            'name'  => 'created_at',
            'label' => __('tag.fields.created_at'),
        ]);
        CRUD::addColumn([
            'name'  => 'updated_at',
            'label' => __('tag.fields.updated_at'),
        ]);
    }

    
    
    /*
    |--------------------------------------------------------------------------
    | CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Store a new tag
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $validated = $this->crud->validateRequest()->array();

        $response = $this->traitStore();

        $item = $this->crud->getCurrentEntry();

        $this->saveTranslation($item, $validated['translations']);

        $this->saveSeo($item, $validated['seo'] ?? []);

        return $response;
    }

    /**
     * Update a tag
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function update()
    {
        $validated = $this->crud->validateRequest()->array();

        $response = $this->traitUpdate();

        $item = $this->crud->getCurrentEntry();

        $this->saveTranslation($item, $validated['translations']);

        $this->saveSeo($item, $validated['seo'] ?? []);

        return $response;
    }


    /*
    |--------------------------------------------------------------------------
    | FIELDS & COLUMNS
    |--------------------------------------------------------------------------
    */

    private function getListFields()
    {
        CRUD::addColumn([
            'name'  => 'display_name',
            'label' => __('tag.fields.name'),
            'type'  => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('translations', function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "{$searchTerm}%");
                });
            },
        ]);
        CRUD::addColumn([
            'name'  => 'available_locales',
            'label' => __('tag.fields.available_locales'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return $entry->available_locales;
            },
        ]);

        CRUD::addColumn([
            'name'  => 'slug',
            'label' => __('tag.fields.slug'),
            'type'  => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('translations', function ($q) use ($searchTerm) {
                    $q->where('slug', 'like', "{$searchTerm}%");
                });
            },
        ]);

        CRUD::addColumn([
            'name'  => 'seo_available_locales',
            'label' => __('tag.fields.seo_available_locales'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return $entry->seo_available_locales;
            },
        ]);

        CRUD::addColumn([
            'name'  => 'homepage',
            'label' => __('tag.fields.homepage'),
            'type'  => 'checkbox',
            'value' => function ($entry) {
                return $entry->homepage;
            },
        ]);
    }

}
