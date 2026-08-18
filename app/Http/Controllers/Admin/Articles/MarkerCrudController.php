<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Traits\CrudTrait;
use App\Http\Requests\Articles\MarkerRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Http\Controllers\Traits\HasLocaleTab;
use App\Models\Articles\Marker;
use App\Models\Articles\Translate\MarkerTranslation;
use App\Support\Permissions\CrudOperation;

class MarkerCrudController extends CrudController
{
    use ChecksCrudPermissions;
    use HasLocaleTab;
    use CrudTrait;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Marker::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/marker');
        CRUD::setEntityNameStrings(__('marker.admin.title_in_singular'), __('marker.admin.title_in_plural'));

        $this->setupCrudPermissions('marker');
        CRUD::setAccessCondition(CrudOperation::DELETE, fn (?Marker $entry = null): bool =>
            $this->hasPermissionForOperation('marker', CrudOperation::DELETE)
            && ($entry === null || ! $entry->isSystem())
        );
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'display_name',
            'label' => __('marker-translation.fields.name'),
            'type' => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('translations', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "{$searchTerm}%");
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'icon',
            'label' => __('marker.fields.icon'),
            'type' => 'custom_html',
            'value' => function ($entry) {
                if (str_starts_with($entry->icon, '<svg') || str_starts_with($entry->icon, '<img')) {
                    return $entry->icon;
                }
                return $entry->icon ? '<i class="' . $entry->icon . '"></i>' : '-';
            },
        ]);

        CRUD::addColumn([
            'name' => 'is_active',
            'label' => __('marker.fields.is_active'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'code',
            'label' => __('marker.fields.code'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'is_system',
            'label' => __('marker.fields.is_system'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name'  => 'available_locales',
            'label' => __('marker.fields.available_locales'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return $entry->available_locales;
            },
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(MarkerRequest::class);
        $this->getCreateOrUpdateFields();
        
        $this->createLocalesTabsWrapper(MarkerTranslation::class);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(MarkerRequest::class);
        $this->getCreateOrUpdateFields();
        
        $markerId = request()->route('id') ?? request()->route('marker');
        
        $this->createLocalesTabsWrapper(MarkerTranslation::class, $markerId, 'marker_id');
    }

    protected function setupShowOperation()
    {
        CRUD::addColumn([
            'name' => 'display_name',
            'label' => __('marker-translation.fields.name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'icon',
            'label' => __('marker.fields.icon'),
            'type' => 'custom_html',
            'value' => function ($entry) {
                if (str_starts_with($entry->icon, '<svg') || str_starts_with($entry->icon, '<img')) {
                    return $entry->icon;
                }
                return $entry->icon ? '<i class="' . $entry->icon . '"></i>' : '-';
            },
        ]);

        CRUD::addColumn([
            'name' => 'is_active',
            'label' => __('marker.fields.is_active'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name'  => 'available_locales',
            'label' => __('marker.fields.available_locales'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return $entry->available_locales;
            },
        ]);

        // Show all translations
        CRUD::addColumn([
            'name' => 'translations',
            'label' => __('marker-translation.admin.title_in_plural'),
            'type' => 'table',
            'columns' => [
                'locale' => __('marker-translation.fields.locale'),
                'name' => __('marker-translation.fields.name'),
            ],
            'value' => function ($entry) {
                return $entry->translations->map(function ($translation) {
                    return [
                        'locale' => $translation->locale,
                        'name' => $translation->name,
                    ];
                })->toArray();
            },
        ]);
    }

    /**
     * Store a new marker
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $validated = $this->crud->validateRequest()->array();

        $response = $this->traitStore();

        $item = $this->crud->getCurrentEntry();

        $this->saveTranslation($item, $validated['translations']);

        return $response;
    }

    /**
     * Update an existing marker
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        $validated = $this->crud->validateRequest()->array();

        $response = $this->traitUpdate();

        $item = $this->crud->getCurrentEntry();

        $this->saveTranslation($item, $validated['translations']);

        return $response;
    }

    /**
     * Видаляє лише звичайний маркер.
     *
     * @return \Illuminate\Http\Response|string
     */
    public function destroy($id)
    {
        $marker = Marker::query()->findOrFail($id);

        $this->crud->hasAccessOrFail(CrudOperation::DELETE, $marker);

        return $this->traitDestroy($id);
    }

    private function getCreateOrUpdateFields()
    {
        $marker = $this->crud->getCurrentEntry();
        $isSystemMarker = $marker instanceof Marker && $marker->isSystem();

        CRUD::addField([
            'name' => 'code',
            'label' => __('marker.fields.code'),
            'type' => 'text',
            'attributes' => $isSystemMarker ? ['readonly' => 'readonly'] : [],
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'is_system_information',
            'type' => 'custom_html',
            'value' => view('admin.markers.system-information', [
                'isSystem' => $isSystemMarker,
            ])->render(),
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'icon',
            'label' => __('marker.fields.icon'),
            'type' => 'textarea',
            'hint' => __('marker.hints.icon'),
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        if (! $isSystemMarker) {
            CRUD::addField([
                'name' => 'is_active',
                'label' => __('marker.fields.is_active'),
                'type' => 'boolean',
                'wrapper' => ['class' => 'form-group col-md-12'],
            ]);
        }
    }

}
