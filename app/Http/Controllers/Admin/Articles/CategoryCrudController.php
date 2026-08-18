<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Traits\CrudTrait;
use App\Http\Requests\Articles\CategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Http\Controllers\Traits\HasLocaleTab;
use App\Http\Controllers\Traits\HasSeoTab;
use \App\Models\Articles\Category;
use \App\Models\Site\Site;
use \App\Models\Articles\Translate\CategoryTranslation;

class CategoryCrudController extends CrudController
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
    use \Backpack\CRUD\app\Http\Controllers\Operations\ReorderOperation;


    public function setup()
    {
        CRUD::setModel(Category::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/category');
        CRUD::setEntityNameStrings(__('category.admin.title_in_singular'), __('category.admin.title_in_plural'));

        $this->setupCrudPermissions('category');
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
        CRUD::setValidation(CategoryRequest::class);
        
        $this->getCreateOrUpdateFields();

        $this->createLocalesTabsWrapper(CategoryTranslation::class);
        
        $this->createSeoTabs(Category::class);
    }

    /**
     * Setup the update operation
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(CategoryRequest::class);

        $this->getCreateOrUpdateFields();
        
        $categoryId = request()->route('id') ?? request()->route('category');
        
        $this->createLocalesTabsWrapper(CategoryTranslation::class, $categoryId, 'category_id');
        
        $this->createSeoTabs(Category::class, $categoryId);
    }

    protected function setupReorderOperation()
    {
        CRUD::set('reorder.label', 'slug');
        CRUD::set('reorder.max_level', 2);

        $model = new Category();
        CRUD::setOperationSetting('reorderColumnNames', [
            'parent_id' => $model->getParentIdName(),
            'lft' => $model->getLftName(),
            'rgt' => $model->getRgtName(),
            'depth' => $model->getDepthName(),
        ]);
    }

    protected function setupShowOperation()
    {
        CRUD::addColumn([
            'name'  => 'site_id',
            'label' => __('category.fields.site_id'),
            'type'  => 'closure',
            'function' => function ($entry) {
                if ($entry->site) {
                    return $entry->site->name;
                }
                return '-';
            },
        ]);
        CRUD::addColumn([
            'name'  => 'slug',
            'label' => __('category.fields.slug'),
            'type'  => 'closure',
            'function' => function ($entry) {
                $depth = 0;
                if (method_exists($entry, 'getDepth') && $entry->exists && $entry->_lft !== null) {
                    try {
                        $depth = $entry->getDepth();
                    } catch (\Exception $e) {
                        $depth = 0;
                    }
                }
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                return $indent . $entry->slug;
            },
        ]);
        CRUD::addColumn([
            'name'  => 'homepage',
            'label' => __('category.fields.homepage'),
            'type'  => 'boolean',
        ]);
        CRUD::addColumn([
            'name'  => 'subdomain',
            'label' => __('category.fields.subdomain'),
            'type'  => 'boolean',
        ]);
        CRUD::addColumn([
            'name'  => 'parent_id',
            'label' => __('category.fields.parent_id'),
            'type'  => 'closure',
            'function' => function ($entry) {
                if ($entry->parent) {
                    return $entry->parent->display_name;
                }
                return '-';
            },
        ]);

        CRUD::addColumn([
            'name'  => 'created_at',
            'label' => __('category.fields.created_at'),
        ]);
        CRUD::addColumn([
            'name'  => 'updated_at',
            'label' => __('category.fields.updated_at'),
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Store a new category
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
     * Update an existing category
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
    | FETCH
    |--------------------------------------------------------------------------
    */

    /**
     * Fetch children categories of the specified parent category
     *
     * This method retrieves and returns child categories for a given parent category ID.
     * It supports search functionality to filter categories by slug.
     * The parent category ID can be passed via form fields or as a query parameter.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing array of child categories with id and slug
     */
    public function fetchChildren()
    {
        $request = request()->all();
        $parentId = null;
        $search = request()->get('q');
        
        if (isset($request['form']) && is_array($request['form'])) {
            foreach ($request['form'] as $field) {
                if (isset($field['name']) && $field['name'] === 'parent_category' && isset($field['value'])) {
                    $parentId = $field['value'];
                    break;
                }
            }
        }
        
        if (!$parentId) {
            $parentId = request()->get('parent_category');
        }
        
        
        if (!$parentId) {
            return response()->json(['results' => []]);
        }
        
        $query = \App\Models\Articles\Category::where('parent_id', $parentId);
        
        if ($search) {
            $query->where('slug', 'like', '%' . $search . '%');
        }
        
        $categories = $query->get()->map(function ($category) {
            return [
                'id' => $category->id,
                $category->getKeyName() => $category->id,
                'slug' => $category->slug,
            ];
        })->values()->toArray();
        
        
        return response()->json($categories);
    }

    
    /*
    |--------------------------------------------------------------------------
    | FIELDS & COLUMNS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the main fields
     *
     * @return void
    */
    private function getListFields()
    {
        CRUD::addClause('with', ['site', 'parent']);

        CRUD::addColumn([
            'name'  => 'site_id',
            'label' => __('category.fields.site_id'),
            'type'  => 'closure',
            'function' => function ($entry) {
                if ($entry->site) {
                    return $entry->site->name;
                }
                return '-';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('site', function ($siteQuery) use ($searchTerm) {
                    $siteQuery->where('name', 'like', "%{$searchTerm}%");
                });
            },
        ]);
        CRUD::addColumn([
            'name'  => 'slug',
            'label' => __('category.fields.slug'),
            'type'  => 'closure',
            'function' => function ($entry) {
                $depth = 0;
                if (method_exists($entry, 'getDepth') && $entry->exists && $entry->_lft !== null) {
                    try {
                        $depth = $entry->getDepth();
                    } catch (\Exception $e) {
                        $depth = 0;
                    }
                }
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                return $indent . $entry->slug;
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('categories.slug', 'like', "%{$searchTerm}%");
            },
        ]);
        CRUD::addColumn([
            'name'  => 'homepage',
            'label' => __('category.fields.homepage'),
            'type'  => 'boolean',
        ]);
        CRUD::addColumn([
            'name'  => 'subdomain',
            'label' => __('category.fields.subdomain'),
            'type'  => 'boolean',
        ]);
        CRUD::addColumn([
            'name'  => 'parent_id',
            'label' => __('category.fields.parent_id'),
            'type'  => 'closure',
            'function' => function ($entry) {
                if ($entry->parent) {
                    return $entry->parent->display_name;
                }
                return '-';
            },
        ]);
        CRUD::addColumn([
            'name'  => 'available_locales',
            'label' => __('category.fields.available_locales'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return $entry->available_locales;
            },
        ]);
        CRUD::addColumn([
            'name'  => 'seo_available_locales',
            'label' => __('category.fields.seo_available_locales'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return $entry->seo_available_locales;
            },
        ]);
        CRUD::addFilter([
            'name'  => 'site_id',
            'type'  => 'dropdown',
            'label' => __('category.fields.site_id'),
        ], function () {
            return Site::pluck('name', 'id')->toArray();
        }, function ($value) {
            CRUD::addClause('where', 'site_id', $value);
        });
        CRUD::addFilter([
            'name'  => 'parent_id',
            'type'  => 'dropdown',
            'label' => __('category.fields.parent_id'),
        ], function () {
            return Category::orderBy('slug')->get()->pluck('display_name', 'id')->toArray();
        }, function ($value) {
            CRUD::addClause('where', 'parent_id', $value);
        });
    }

    /**
     * Get the create or update fields
     *
     * @return void
     */
    private function getCreateOrUpdateFields()
    {
        CRUD::addField([
            'name'    => 'site_id',
            'label'   => __('category.fields.site_id'),
            'entity'  => 'site',
            'attribute' => 'name',
            'model' => Site::class,
            'type'    => 'select2',
            'wrapper'=> ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
        ]);
        CRUD::addField([
            'name'    => 'parent_id',
            'label'   => __('category.fields.parent_id'),
            'entity'  => 'parent',
            'attribute' => 'display_name',
            'model'     => Category::class,
            'type'      => 'select2',
            'wrapper'=> ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
        ]);
        CRUD::addField([
            'name' => 'slug',
            'label' => __('category.fields.slug'),
            'type' => 'text',
            'wrapper'=> ['class' => 'form-group col-md-12'],
            'tab' => __('admin.tabs.general'),
        ]);
        CRUD::addField([
            'name'  => 'homepage',
            'label' => __('category.fields.homepage'),
            'type'  => 'checkbox',
            'wrapper'=> ['class' => 'form-group col-md-12'],
            'tab' => __('admin.tabs.general'),
        ]);
        CRUD::addField([
            'name'  => 'subdomain',
            'label' => __('category.fields.subdomain'),
            'type'  => 'checkbox',
            'wrapper'=> ['class' => 'form-group col-md-12'],
            'tab' => __('admin.tabs.general'),
        ]);
    }
}
