<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Requests\Articles\ArticleFieldConfigurationRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Models\Articles\ArticleFieldConfiguration;
use App\Models\Articles\ArticleType;

class ArticleFieldConfigurationCrudController extends CrudController
{
    use ChecksCrudPermissions;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(ArticleFieldConfiguration::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/article-field-configuration');
        CRUD::setEntityNameStrings(__('article-field-configuration.admin.title_in_singular'), __('article-field-configuration.admin.title_in_plural'));

        $this->setupCrudPermissions('article-field-configuration');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'field_name',
            'label' => __('article-field-configuration.fields.field_name'),
        ]);

        CRUD::addColumn([
            'name' => 'article_type_id',
            'label' => __('article-field-configuration.fields.article_type_id'),
            'type' => 'closure',
            'function' => function ($entry) {
                if (! $entry->article_type_id) {
                    return __('admin.tabs.general');
                }

                $type = ArticleType::query()->find($entry->article_type_id);

                return $type?->display_name ?? $entry->article_type_id;
            },
        ]);

        CRUD::addColumn([
            'name' => 'is_required',
            'label' => __('article-field-configuration.fields.is_required'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'is_visible',
            'label' => __('article-field-configuration.fields.is_visible'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'max_length',
            'label' => __('article-field-configuration.fields.max_length'),
        ]);

        CRUD::addColumn([
            'name' => 'position',
            'label' => __('article-field-configuration.fields.position'),
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArticleFieldConfigurationRequest::class);
        $this->getCreateOrUpdateFields();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
        
        // Load validation_rules_string for editing
        $entry = $this->crud->getCurrentEntry();
        if ($entry && $entry->validation_rules_string) {
            CRUD::modifyField('validation_rules_string', [
                'default' => $entry->validation_rules_string,
            ]);
        }
    }

    public function store()
    {
        $this->processValidationRules();
        return $this->traitStore();
    }

    public function update()
    {
        $this->processValidationRules();
        return $this->traitUpdate();
    }

    private function processValidationRules(): void
    {
        $request = request();
        if ($request->has('validation_rules_string')) {
            $rulesString = $request->input('validation_rules_string');
            if (!empty($rulesString)) {
                $rules = array_map('trim', explode(',', $rulesString));
                $request->merge(['validation_rules' => array_filter($rules)]);
            } else {
                $request->merge(['validation_rules' => null]);
            }
            $request->request->remove('validation_rules_string');
        }
    }

    private function getCreateOrUpdateFields()
    {
        CRUD::addField([
            'name' => 'article_type_id',
            'label' => __('article-field-configuration.fields.article_type_id'),
            'type' => 'select2',
            'entity' => false,
            'attribute' => 'display_name',
            'model' => ArticleType::class,
            'allows_null' => true,
            'options' => function ($query) {
                return $query->where('is_active', true)->orderBy('code')->get();
            },
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'field_name',
            'label' => __('article-field-configuration.fields.field_name'),
            'type' => 'select_from_array',
            'options' => [
                // Translation fields
                'title' => __('article-translate.fields.title'),
                'excerpt' => __('article-translate.fields.excerpt'),
                'content' => __('article-translate.fields.content'),
                'slug' => __('article-translate.fields.slug'),
                // Article fields
                'site_id' => __('article.fields.sites'),
                'category_id' => __('article.fields.category_id'),
                'type_id' => __('article.fields.type'),
                'status' => __('article.fields.status'),
                'published_at' => __('article.fields.published_at'),
                'thumbnail' => __('article.fields.thumbnail'),
                'source_url' => __('article.fields.source_url'),
                'authors' => __('article.fields.authors'),
                'editors' => __('article.fields.editors'),
                'tags' => __('article.fields.tags'),
                'markers' => __('article.fields.markers'),

                // Type-specific meta fields (stored in articles_meta)
                'youtube_id' => __('article.fields.youtube_id'),

                'company_edrpou' => __('admin.account.company.edrpou'),
                'company_website' => __('admin.account.company.website'),
                'company_social' => __('admin.account.company.social'),
                'company_phone' => __('admin.account.company.phone'),
                'company_director' => __('admin.account.company.director'),
                'company_position' => __('admin.account.company.position'),
                'company_type' => __('admin.account.company.type'),
            ],
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'is_required',
            'label' => __('article-field-configuration.fields.is_required'),
            'type' => 'boolean',
            'wrapper' => ['class' => 'form-group col-md-3'],
        ]);

        CRUD::addField([
            'name' => 'is_visible',
            'label' => __('article-field-configuration.fields.is_visible'),
            'type' => 'boolean',
            'wrapper' => ['class' => 'form-group col-md-3'],
        ]);

        CRUD::addField([
            'name' => 'position',
            'label' => __('article-field-configuration.fields.position'),
            'type' => 'number',
            'default' => 0,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'min_length',
            'label' => __('article-field-configuration.fields.min_length'),
            'type' => 'number',
            'wrapper' => ['class' => 'form-group col-md-3'],
        ]);

        CRUD::addField([
            'name' => 'max_length',
            'label' => __('article-field-configuration.fields.max_length'),
            'type' => 'number',
            'wrapper' => ['class' => 'form-group col-md-3'],
        ]);

        CRUD::addField([
            'name' => 'validation_rules_string',
            'label' => __('article-field-configuration.fields.validation_rules'),
            'type' => 'textarea',
            'hint' => __('article-field-configuration.hints.validation_rules'),
            'attributes' => [
                'placeholder' => 'email,url,unique:table,column',
            ],
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);
    }
}
