<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Requests\Articles\ArticleTypeRequest;
use App\Jobs\ManualArticleStaticInvalidationJob;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Http\Controllers\Traits\CrudTrait;
use App\Http\Controllers\Traits\HasLocaleTab;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Translate\ArticleTypeTranslation;

class ArticleTypeCrudController extends CrudController
{
    use ChecksCrudPermissions;
    use CrudTrait;
    use HasLocaleTab;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(ArticleType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/article-type');
        CRUD::setEntityNameStrings(__('article-type.admin.title_in_singular'), __('article-type.admin.title_in_plural'));

        $this->setupCrudPermissions('article-type');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'code',
            'label' => __('article-type.fields.code'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('article_types.code', 'like', "%{$searchTerm}%");
            },
        ]);

        CRUD::addColumn([
            'name' => 'display_name',
            'label' => __('article-type.fields.name'),
            'type' => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('translations', function ($translationQuery) use ($searchTerm) {
                    $translationQuery->where('name', 'like', "%{$searchTerm}%");
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'is_active',
            'label' => __('article-type.fields.is_active'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name'  => 'homepage',
            'label' => __('category.fields.homepage'),
            'type'  => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'is_splittable',
            'label' => __('article-type.fields.is_splittable'),
            'type' => 'boolean',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArticleTypeRequest::class);
        $this->getCreateOrUpdateFields();
        
        $this->createLocalesTabsWrapper(ArticleTypeTranslation::class);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(ArticleTypeRequest::class);
        $this->getCreateOrUpdateFields();
        
        $articleTypeId = request()->route('id') ?? request()->route('article-type');
        $this->createLocalesTabsWrapper(ArticleTypeTranslation::class, $articleTypeId, 'article_type_id');
    }

    private function getCreateOrUpdateFields()
    {
        CRUD::addField([
            'name' => 'code',
            'label' => __('article-type.fields.code'),
            'type' => 'text',
            'hint' => __('article-type.hints.code'),
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => __('article-type.fields.is_active'),
            'type' => 'boolean',
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name'  => 'homepage',
            'label' => __('category.fields.homepage'),
            'type'  => 'checkbox',
            'wrapper'=> ['class' => 'form-group col-md-12'],
            'tab' => __('admin.tabs.general'),
        ]);

        CRUD::addField([
            'name' => 'is_splittable',
            'label' => __('article-type.fields.is_splittable'),
            'type' => 'checkbox',
            'hint' => __('article-type.hints.is_splittable'),
            'wrapper'=> ['class' => 'form-group col-md-12'],
            'tab' => __('admin.tabs.general'),
        ]);
    }

    public function store()
    {
        $validated = $this->crud->validateRequest()->array();

        $response = $this->traitStore();

        $item = $this->crud->getCurrentEntry();

        $this->saveTranslation($item, $validated['translations'] ?? []);

        return $response;
    }

    protected function update()
    {
        $validated = $this->crud->validateRequest()->array();

        $articleTypeId = request()->route('id') ?? request()->route('article-type');

        $wasSplittable = ArticleType::query()
            ->whereKey($articleTypeId)
            ->value('is_splittable');

        $response = $this->traitUpdate();

        $item = $this->crud->getCurrentEntry();

        $this->saveTranslation($item, $validated['translations'] ?? []);

        // for auto type invalidate
//        if ($wasSplittable !== null && (bool) $wasSplittable !== (bool) $item->is_splittable) {
//            ManualArticleStaticInvalidationJob::dispatch($item->code);
//        }

        return $response;
    }
}
