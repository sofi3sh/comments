<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Models\Articles\ArticlesBlockSetting;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Category;
use App\Models\Articles\Marker;
use App\Models\Site\Site;
use App\Services\Article\ArticlesBlockSettingsService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class ArticlesBlockSettingsCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;
    use ChecksCrudPermissions;

    public  int $maxViewPeriod;

    public function __construct()
    {
        $this->maxViewPeriod = ArticlesBlockSetting::getMaxViewPeriod();

        parent::__construct();
    }

    private const BLOCKS = [
        ArticlesBlockSetting::MAIN_CONTAINER_RIGHT     => ['label' => 'admin.articles_block_settings.blocks.main-container-right'],
        ArticlesBlockSetting::MAIN_CONTAINER_LEFT      => ['label' => 'admin.articles_block_settings.blocks.main-container-left'],
        ArticlesBlockSetting::SWIPER_CONTAINER         => ['label' => 'admin.articles_block_settings.blocks.swiper-container'],
        ArticlesBlockSetting::ARTICLES_CONTAINER_LEFT  => ['label' => 'admin.articles_block_settings.blocks.articles-container-left'],
        ArticlesBlockSetting::ARTICLES_CONTAINER_RIGHT => ['label' => 'admin.articles_block_settings.blocks.articles-container-right'],
        ArticlesBlockSetting::LATEST_MATERIALS         => ['label' => 'admin.articles_block_settings.blocks.latest-materials'],
        ArticlesBlockSetting::VIDEO_MATERIALS          => ['label' => 'admin.articles_block_settings.blocks.video-materials'],
    ];

    public function setup()
    {
        CRUD::setModel(ArticlesBlockSetting::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/articles-block-settings');
        CRUD::setEntityNameStrings('Вивід статей у блоках', 'Вивід статей у блоках');

        $this->setupCrudPermissions('articles-block-settings');
    }

    protected function setupListOperation()
    {
        $this->crud->denyAccess('list');
    }

    protected function setupCreateOperation()
    {
        $request = $this->crud->getRequest();

        $siteId = (int) $request->query('site_id', 0);
        if ($siteId <= 0) {
            $siteId = (int) Site::query()->where('active', true)->value('id');
        }
        if ($siteId <= 0) {
            $siteId = (int) Site::query()->value('id');
        }

        $settings = ArticlesBlockSetting::query()
            ->where('site_id', $siteId)
            ->get()
            ->keyBy('block_key');

        // Data for dropdowns/selects.
        $sites = Site::query()->where('active', true)->get();
        $categories = Category::query()
            ->whereHas('site', function ($q) use ($siteId) {
                $q->where('id', $siteId);
            })
            ->with(['translations'])
            ->get();

        $types = ArticleType::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $markers = Marker::query()->orderBy('id')->get();

        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['Blogger', 'Staff writer'])
            ->pluck('id')
            ->all();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->when(! empty($roleIds), function ($q) use ($roleIds) {
                $q->whereIn('id', $roleIds);
            })
            ->orderBy('id')
            ->get();

        $this->crud->enableTabs();

        CRUD::addField([
            'name' => 'site_id',
            'label' => __('admin.articles_block_settings.labels.site'),
            'type' => 'select2',
            'entity' => 'site',
            'attribute' => 'name',
            'model' => Site::class,
            'value' => $siteId,
            'options' => function ($query) use ($sites) {
                return $sites->isEmpty() ? $query->get() : $sites;
            },
            'wrapper' => ['class' => 'form-group col-md-4'],
            'attributes' => [
                'onchange' => "window.location.href='".backpack_url('articles-block-settings/create')."?site_id='+this.value",
            ],
        ]);

        foreach (self::BLOCKS as $blockKey => $meta) {
            $setting = $settings->get($blockKey);

            $isActive = $setting?->is_active ?? true;
            $limit = $setting?->limit ?? 8;
            $orderBy = $setting?->order_by ?? 'views';
            $orderDirection = $setting?->order_direction ?? 'desc';

            $viewsWindowHours = $setting?->views_window_hours;
            $refreshIntervalHours = $setting?->refresh_interval_hours ?? 4;

            $categoryId = $setting?->category_id;
            $typeId = $setting?->type_id;

            /** @var list<int> $authorRoleIds */
            $authorRoleIds = $setting?->author_role_ids ?? [];
            /** @var list<int> $markerIds */
            $markerIds = $setting?->marker_ids ?? [];

            $publishedFrom = $setting?->published_at_from?->format('Y-m-d\\TH:i');
            $publishedTo = $setting?->published_at_to?->format('Y-m-d\\TH:i');
            $updatedFrom = $setting?->updated_at_from?->format('Y-m-d\\TH:i');
            $updatedTo = $setting?->updated_at_to?->format('Y-m-d\\TH:i');

            $tabLabel = __($meta['label']);

            /** CUSTOM BLOCK */
            CRUD::addField([
                'name' => "{$blockKey}_layout_start",
                'type' => 'custom_html',
                'value' => view('admin.settings.crud-start', compact('blockKey'))->render(),
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_is_active",
                'label' => __('admin.articles_block_settings.labels.active'),
                'type' => 'boolean',
                'value' => (bool) $isActive,
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_limit",
                'label' => __('admin.articles_block_settings.labels.limit_articles'),
                'type' => 'number',
                'value' => (int) $limit,
                'attributes' => ['min' => 1, 'max' => 50],
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_order_by",
                'label' => __('admin.articles_block_settings.labels.sorting'),
                'type' => 'select_from_array',
                'allows_null' => true,
                'options' => [
                    'views' => __('admin.articles_block_settings.order_by.views'),
                    'published_at' => __('admin.articles_block_settings.order_by.published_at'),
                    'updated_at' => __('admin.articles_block_settings.order_by.updated_at'),
                ],
                'value' => $orderBy,
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_order_direction",
                'label' => __('admin.articles_block_settings.labels.direction'),
                'type' => 'select_from_array',
                'allows_null' => true,
                'options' => [
                    'desc' => __('admin.order_direction.desc'),
                    'asc' => __('admin.order_direction.asc'),
                ],
                'value' => $orderDirection,
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_views_window_hours",
                'label' => __('admin.articles_block_settings.labels.views_window_hours'),
                'type' => 'number',
                'value' => $viewsWindowHours,
                'attributes' => ['min' => 1, 'max' => $this->maxViewPeriod],
                'wrapper' => ['class' => 'form-group col-md-4'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_refresh_interval_hours",
                'label' => __('admin.articles_block_settings.labels.refresh_interval_hours'),
                'type' => 'number',
                'value' => (int) $refreshIntervalHours,
                'attributes' => ['min' => 1, 'max' => 168],
                'wrapper' => ['class' => 'form-group col-md-4'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_category_id",
                'label' => __('admin.articles_block_settings.labels.category'),
                'type' => 'select2',
                'entity' => 'category',
                'attribute' => 'display_name',
                'model' => Category::class,
                'value' => $categoryId,
                'allows_null' => true,
                'options' => function ($query) use ($categories) {
                    return $categories;
                },
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_type_id",
                'label' => __('admin.articles_block_settings.labels.type'),
                'type' => 'select2',
                'entity' => 'articleType',
                'attribute' => 'display_name',
                'model' => ArticleType::class,
                'value' => $typeId,
                'allows_null' => true,
                'options' => function ($query) use ($types) {
                    return $types;
                },
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_author_role_ids",
                'label' => __('admin.articles_block_settings.labels.author_roles'),
                'type' => 'select2_multiple',
                'entity' => false,
                'attribute' => 'name',
                'model' => Role::class,
                'value' => $authorRoleIds,
                'options' => function ($query) use ($roles) {
                    return $roles;
                },
                'wrapper' => ['class' => 'form-group col-md-6'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_marker_ids",
                'label' => __('admin.articles_block_settings.labels.markers'),
                'type' => 'select2_multiple',
                'entity' => false,
                'attribute' => 'display_name',
                'model' => Marker::class,
                'value' => $markerIds,
                'options' => function ($query) use ($markers) {
                    return $markers;
                },
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_published_at_from",
                'label' => __('admin.articles_block_settings.labels.published_at_from'),
                'type' => 'datetime',
                'value' => $publishedFrom,
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_published_at_to",
                'label' => __('admin.articles_block_settings.labels.published_at_to'),
                'type' => 'datetime',
                'value' => $publishedTo,
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_updated_at_from",
                'label' => __('admin.articles_block_settings.labels.updated_at_from'),
                'type' => 'datetime',
                'value' => $updatedFrom,
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            CRUD::addField([
                'name' => "{$blockKey}_updated_at_to",
                'label' => __('admin.articles_block_settings.labels.updated_at_to'),
                'type' => 'datetime',
                'value' => $updatedTo,
                'wrapper' => ['class' => 'form-group col-md-3'],
                'tab' => $tabLabel,
            ]);

            /** CLOSE CUSTOM BLOCK */
            CRUD::addField([
                'name' => "{$blockKey}_layout_end",
                'type' => 'custom_html',
                'value' => view('admin.settings.crud-end'),
                'tab' => $tabLabel,
            ]);
        }
    }

    protected function setupUpdateOperation()
    {
        $this->crud->denyAccess('update');
    }

    protected function setupDeleteOperation()
    {
        $this->crud->denyAccess('delete');
    }

    protected function setupShowOperation()
    {
        $this->crud->denyAccess('show');
    }

    public function store(): RedirectResponse
    {
        /** @var Request $request */
        $request = $this->crud->getRequest();

        $allowedBlockKeys = array_keys(self::BLOCKS);
        $rules = [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
        ];

        foreach ($allowedBlockKeys as $blockKey) {
            $rules["{$blockKey}_is_active"] = ['nullable', 'boolean'];
            $rules["{$blockKey}_limit"] = ['required', 'integer', 'min:1', 'max:50'];
            $rules["{$blockKey}_order_by"] = ['nullable', 'in:views,published_at,updated_at'];
            $rules["{$blockKey}_order_direction"] = ['nullable', 'in:asc,desc'];
            $rules["{$blockKey}_views_window_hours"] = ['nullable', 'integer', 'min:1', 'max:'.$this->maxViewPeriod];
            $rules["{$blockKey}_refresh_interval_hours"] = ['required', 'integer', 'min:1', 'max:168'];

            $rules["{$blockKey}_category_id"] = ['nullable', 'integer', 'exists:categories,id'];
            $rules["{$blockKey}_type_id"] = ['nullable', 'integer', 'exists:article_types,id'];

            $rules["{$blockKey}_published_at_from"] = ['nullable', 'date'];
            $rules["{$blockKey}_published_at_to"] = ['nullable', 'date'];
            $rules["{$blockKey}_updated_at_from"] = ['nullable', 'date'];
            $rules["{$blockKey}_updated_at_to"] = ['nullable', 'date'];

            $rules["{$blockKey}_author_role_ids"] = ['nullable'];
            $rules["{$blockKey}_marker_ids"] = ['nullable'];
        }

        $validated = $request->validate($rules);

        $siteId = (int) $validated['site_id'];

        foreach (self::BLOCKS as $blockKey => $_) {
            $payload = [
                'site_id' => $siteId,
                'block_key' => $blockKey,
                'is_active' => Arr::get($validated, "{$blockKey}_is_active", true) ? 1 : 0,
                'limit' => (int) Arr::get($validated, "{$blockKey}_limit", 8),
                'order_by' => $this->normalizeNullableString(Arr::get($validated, "{$blockKey}_order_by")),
                'order_direction' => $this->normalizeNullableString(Arr::get($validated, "{$blockKey}_order_direction")),
                'views_window_hours' => $this->normalizeNullableInt(Arr::get($validated, "{$blockKey}_views_window_hours")),
                'refresh_interval_hours' => (int) Arr::get($validated, "{$blockKey}_refresh_interval_hours", 4),

                'category_id' => $this->normalizeNullableInt(Arr::get($validated, "{$blockKey}_category_id")),
                'type_id' => $this->normalizeNullableInt(Arr::get($validated, "{$blockKey}_type_id")),

                'author_role_ids' => $this->toJsonArrayOrNull(Arr::get($validated, "{$blockKey}_author_role_ids")),
                'marker_ids' => $this->toJsonArrayOrNull(Arr::get($validated, "{$blockKey}_marker_ids")),

                'published_at_from' => $this->parseNullableTimestamp(Arr::get($validated, "{$blockKey}_published_at_from")),
                'published_at_to' => $this->parseNullableTimestamp(Arr::get($validated, "{$blockKey}_published_at_to")),
                'updated_at_from' => $this->parseNullableTimestamp(Arr::get($validated, "{$blockKey}_updated_at_from")),
                'updated_at_to' => $this->parseNullableTimestamp(Arr::get($validated, "{$blockKey}_updated_at_to")),
            ];

            ArticlesBlockSetting::query()->updateOrCreate(
                ['site_id' => $siteId, 'block_key' => $blockKey],
                $payload
            );

            // Очищаємо обидва формати результату після зміни налаштувань блоку.
            app(ArticlesBlockSettingsService::class)
                ->forgetArticlesForBlockCache($siteId, $blockKey);
        }

        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        return redirect()->back();
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === false) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function normalizeNullableString(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = trim($value);

        return $value;
    }

    private function toJsonArrayOrNull(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_array($value)) {
            return null;
        }

        $value = array_values(array_filter($value, static fn ($v) => $v !== null && $v !== ''));
        if (empty($value)) {
            return null;
        }

        return array_map(static fn ($v) => (int) $v, $value);
    }

    private function parseNullableTimestamp(mixed $value): ?Carbon
    {
        $value = is_string($value) ? trim($value) : null;
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
