<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Requests\Articles\AttachmentCreateRequest;
use App\Http\Requests\Articles\AttachmentUpdateRequest;
use App\Services\Attachment\AttachmentUploadService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Models\Articles\Attachment;
use App\Models\Articles\Tag;

class AttachmentCrudController extends CrudController
{
    use ChecksCrudPermissions;
    
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function __construct(
        protected AttachmentUploadService $uploadService
    ) {
        parent::__construct();
    }

    public function setup()
    {
        CRUD::setModel(Attachment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/attachment');
        CRUD::setEntityNameStrings(__('attachment.admin.title_in_singular'), __('attachment.admin.title_in_plural'));

        $this->setupCrudPermissions('attachment');
    }

    /**
     * Setup the list operation
     *
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->query->parents()->with('tags');

        $user = backpack_user();
        if ($user && !$user->hasRole('Admin', 'web')) {
            $this->crud->query->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('is_public', true);
            });
        }

        CRUD::addColumn([
            'name'  => 'id',
            'label' => __('attachment.fields.id'),
        ]);
        
        CRUD::addColumn([
            'name'  => 'thumbnail',
            'label' => __('attachment.fields.preview'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                return view('admin.attachment.preview', [
                    'entry' => $entry,
                    'maxWidth' => 100,
                    'maxHeight' => 100,
                ])->render();
            },
        ]);

        CRUD::addColumn([
            'name'  => 'filename',
            'label' => __('attachment.fields.filename'),
        ]);

        CRUD::addColumn([
            'name'  => 'mime_type',
            'label' => __('attachment.fields.mime_type'),
        ]);

        CRUD::addColumn([
            'name'  => 'formatted_size',
            'label' => __('attachment.fields.size'),
            'type'  => 'closure',
            'function' => function ($entry) {
                return $entry->formatted_size;
            },
        ]);

        CRUD::addColumn([
            'name'  => 'alt',
            'label' => __('attachment.fields.alt'),
        ]);

        CRUD::addColumn([
            'name'  => 'tags',
            'label' => __('attachment.fields.tags'),
            'type'  => 'closure',
            'function' => function ($entry) {
                return $entry->tags->pluck('name')->join(', ') ?: '—';
            },
        ]);

        CRUD::addColumn([
            'name'  => 'is_public',
            'label' => __('attachment.fields.is_public'),
            'type'  => 'boolean',
        ]);

        CRUD::addColumn([
            'name'  => 'user',
            'label' => __('attachment.fields.user'),
            'type'  => 'closure',
            'function' => function ($entry) {
                return $entry->user ? $entry->user->name : '-';
            },
        ]);

        CRUD::addColumn([
            'name'  => 'created_at',
            'label' => __('attachment.fields.created_at'),
        ]);
    }

    /**
     * Setup the create operation
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        $this->crud->setValidation(AttachmentCreateRequest::class);

        CRUD::addField([
            'name' => 'file',
            'label' => __('attachment.fields.file'),
            'type' => 'upload',
            'upload' => true,
              'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
            'hint' => __('attachment.hints.file'),
        ]);

        CRUD::addField([
            'name' => 'alt',
            'label' => __('attachment.fields.alt'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'title',
            'label' => __('attachment.fields.title'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'caption',
            'label' => __('attachment.fields.caption'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'    => 'tags',
            'label'   => __('attachment.fields.tags'),
            'type'    => 'select2_from_ajax_multiple',
            'entity'  => 'tags',
            'attribute' => 'display_name',
            'model'   => Tag::class,
            'pivot'   => true,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'data_source' => route('api.tags.fetch'),
            'method' => 'GET',
            'include_all_form_fields' => false,
            'placeholder' => __('article.fields.tags'),
            'minimum_input_length' => 3,
        ]);

        CRUD::addField([
            'name' => 'is_public',
            'label' => __('attachment.fields.is_public'),
            'type' => 'checkbox',
            'default' => false,
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
    }

    /**
     * Setup the update operation
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->crud->setValidation(AttachmentUpdateRequest::class);

        $entry = $this->crud->getCurrentEntry();

        CRUD::addField([
            'name' => 'current_file',
            'label' => __('attachment.fields.current_file'),
            'type' => 'custom_html',
            'value' => view('admin.attachment.preview', [
                'entry' => $entry,
                'maxWidth' => 300,
                'maxHeight' => 300,
                'showLabel' => true,
                'showInfo' => true,
            ])->render(),
        ]);

//        CRUD::addField([
//            'name' => 'file',
//            'label' => __('attachment.fields.file_replace'),
//            'type' => 'upload',
//            'upload' => true,
////            'disk' => 'public',
//            'wrapper' => [
//                'class' => 'form-group col-md-12',
//            ],
//            'hint' => __('attachment.hints.file_replace'),
//        ]);

        // CRUD::addField([
        //     'name' => 'filename',
        //     'label' => __('attachment.fields.filename'),
        //     'type' => 'text',
        //     'wrapper' => [
        //         'class' => 'form-group col-md-6',
        //     ],
        // ]);

        CRUD::addField([
            'name' => 'title',
            'label' => __('attachment.fields.title'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'caption',
            'label' => __('attachment.fields.caption'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'tags',
            'label' => __('attachment.fields.tags'),
            'type' => 'select2_from_ajax_multiple',
            'entity' => 'tags',
            'attribute' => 'display_name',
            'model' => Tag::class,
            'pivot' => true,
            'data_source' => route('api.tags.fetch'),
            'method' => 'GET',
            'minimum_input_length' => 2,
            'include_all_form_fields' => false,
            'placeholder' => __('article.fields.tags'),
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'is_public',
            'label' => __('attachment.fields.is_public'),
            'type' => 'checkbox',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
    }


    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function store()
    {
        // validation
        $formRequest = AttachmentCreateRequest::createFromBase(request());
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();
        $validated = $formRequest->validated();

        $request = $this->crud->getRequest();
        $file = $request->file('file');

        if ($file) {
            $attachment = $this->uploadService->upload($file, $validated);

            $this->crud->entry = $attachment;

            return redirect($this->crud->route);
        }

        return $this->traitStore();
    }

}
