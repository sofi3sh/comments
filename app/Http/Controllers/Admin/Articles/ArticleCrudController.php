<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Editor\EditorContentProcessor;
use App\Editor\EditorService;
use App\Exceptions\AdminArticleTitleSearchException;
use App\Http\Controllers\Traits\CrudTrait;
use App\Http\Controllers\Traits\HasMediaTab;
use App\Http\Requests\Articles\ArticleRequest;
use App\Models\Articles\Attachment;
use App\Services\Article\Audit\ArticleAuditHistoryService;
use App\Services\Article\Audit\ArticleContentActivityLogger;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\FetchOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\ChecksArticleCrudPermissions;
use App\Http\Controllers\Traits\HasLocaleTab;
use App\Http\Controllers\Traits\HasSeoTab;
use App\Services\Article\ArticleCacheService;
use App\Services\Article\ArticleDeleteLimitService;
use App\Services\Article\ArticleFieldConfigurationResolver;
use App\Services\Article\BreakingNews;
use App\Services\Article\ArticleLifecycleService;
use App\Services\Article\AdminArticleTitleSearchService;
use App\Services\Article\StaticInvalidateService;
use App\Services\Article\YouTubeVideoResolver;
use App\Support\Permissions\CrudOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use \App\Models\Articles\Article;
use App\Models\Scopes\CurrentSiteScope;
use \App\Models\Articles\Translate\ArticleTranslation;
use \App\Models\Site\Site;
use \App\Models\Articles\Category;
use \App\Models\Articles\Tag;
use \App\Models\Articles\Marker;
use \App\Models\Articles\ArticleType;
use \App\Models\Articles\ArticleMeta;
use \App\Models\Articles\ArticleFieldConfiguration;
use \App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleCrudController extends CrudController
{
    private const BULK_DELETE_LIMIT = 100;

    use ChecksArticleCrudPermissions;
    use HasLocaleTab;
    use HasSeoTab;
    use HasMediaTab;
    use CrudTrait;
    use ListOperation;
    use CreateOperation { store as traitStore; }
    use UpdateOperation { update as traitUpdate; }
    use DeleteOperation { destroy as traitDestroy; }
    use ShowOperation;
    use FetchOperation;

    protected string $type;

    protected int $typeId;

    /**
     * Request-scoped memos for the article-type lookups below.
     *
     * Instance properties rather than `static` locals: a static would be shared
     * by every request the process handles under a worker runtime, so an edited
     * article type would keep serving its old name until the worker restarted.
     * Controllers are resolved per request, so an instance property keeps the
     * memoization without outliving the request.
     *
     * @var array<int, string|null>
     */
    private array $typeDisplayNameMemo = [];

    /** @var array<string, int> */
    private array $typeIdByCodeMemo = [];

    public function setup()
    {
        $this->type = request()->route('type');

        abort_unless($this->type, 404);

        $this->typeId = ArticleType::query()
            ->where('code', $this->type)
            ->where('is_active', true)
            ->value('id');

        abort_unless($this->typeId, 404);

        CRUD::setModel(Article::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/'.Article::PREFIX_ROUTE.'/'.$this->type);
        CRUD::setEntityNameStrings(
            __('article.admin.title_in_singular'),
            __('article.admin.title_in_plural')
        );

        $this->setupArticleCrudPermissions();
        abort_unless(backpack_user() && $this->canAccessArticleTypeCode(backpack_user(), $this->type), 403);
        $this->applyArticleListAccessScope();
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
        $this->applyTypeUiContext($this->typeId);

        $this->crud->addClause('where', 'type_id', $this->typeId);

        $showDeleted = is_backpack_admin() && request()->routeIs('article.deleted*');

        if ($showDeleted) {
            CRUD::setRoute(config('backpack.base.route_prefix').'/'.Article::PREFIX_ROUTE.'/'.$this->type.'/deleted');
            $this->crud->query
                ->withoutGlobalScope(CurrentSiteScope::class)
                ->onlyTrashed();
            CRUD::removeAllButtonsFromStack('line');
        }

        $this->crud->setDefaultPageLength(10);
        $this->crud->setPageLengthMenu([10, 25, 50, 100]);
        $this->crud->query->with([
            'category.parent',
            'articleViewsAggregated',
            'authors',
            'editors',
            'sites',
            'translations' => function ($query) use ($showDeleted) {
                if ($showDeleted) {
                    $query->withTrashed();
                }
            },
        ]);
        $this->getListFields();

        if (is_backpack_admin()) {
            CRUD::addButtonFromView('search', 'article_deleted_toggle', 'article_deleted_toggle', 'beginning');

            if ($showDeleted) {
                CRUD::addColumn([
                    'name' => 'deleted_at',
                    'label' => __('article.deleted.deleted_at'),
                    'type' => 'datetime',
                ]);
                CRUD::addButtonFromView('line', 'article_restore', 'article_restore', 'beginning');
            } else {
                $this->crud->enableBulkActions();
                CRUD::addButtonFromView('bottom', 'bulk_delete_articles', 'bulk_delete_articles', 'beginning');
            }
        }

        if (! $showDeleted && backpack_user() && $this->canUseArticleTypeOperation(backpack_user(), $this->type, CrudOperation::INVALIDATE)) {
            CRUD::addButtonFromView('line', 'invalidate_static', 'invalidate_static', 'end');
        }
    }

    /**
     * Setup the create operation
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArticleRequest::class);
        CRUD::field('name');
        CRUD::field('name')->type('text')->label('URL Segment (slug)');

        $this->applyTypeUiContext($this->typeId);

        $this->getCreateOrUpdateFields();

        $this->createLocalesTabsWrapper(ArticleTranslation::class);

        $this->createSeoTabs(Article::class);
    }

    /**
     * Setup the update operation
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $article = $this->findArticleForAccessCheck($this->getCurrentArticleIdFromRoute());
        abort_unless($this->canUseArticleEntryOperation(backpack_user(), $article, CrudOperation::UPDATE), 403);

        CRUD::setValidation(ArticleRequest::class);

        $this->crud->query->with('meta');

        $this->applyTypeUiContext($this->getTypeIdForCurrentEntry());

        $this->getCreateOrUpdateFields();

        $articleId = request()->route('id') ?? request()->route('article');

        $this->createLocalesTabsWrapper(ArticleTranslation::class, $articleId, 'article_id');

        $this->createSeoTabs(Article::class, $articleId);

        $this->addAuditHistoryField((int) $articleId);
    }

    /**
     * @return void
     */
    protected function setupShowOperation()
    {
        $article = $this->findArticleForAccessCheck($this->getCurrentArticleIdFromRoute());
        abort_unless($this->canUseArticleEntryOperation(backpack_user(), $article, CrudOperation::SHOW), 403);

        $this->crud->query->with('meta');

        $this->getListFields();
        $this->applyTypeUiContext($this->getTypeIdForCurrentEntry());
    }


    /*
    |--------------------------------------------------------------------------
    | CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     *
     * @return \Illuminate\Http\JsonResponse|RedirectResponse
     * @throws AuthenticationException
     * @throws \Throwable
     */
    public function store()
    {
        $user = backpack_user();
        abort_unless($user && $this->canUseArticleTypeCreate($user, $this->type), 403);

        $this->prepareArticleAuthorEditorFieldsForSave($user);

        $validated = $this->crud->validateRequest()->array();

        $this->crud->setSaveAction();

        $this->traitStore();

        /** @var Article $item */
        $item = $this->crud->getCurrentEntry();
        $this->syncArticleSitesFromCategory($item);
        $this->syncArticleAuthorsAfterCreate($item, $user);

        $this->syncBreakingNewsMarker($item);

        $translations = $this->contentProcess($validated);

        $this->saveTranslation($item, $translations);
        $this->saveThumbnailToArticle($item->id);
        $this->saveSeo($item, $validated['seo'] ?? []);
        $this->saveArticleMeta($item);
        app(ArticleCacheService::class)->forgetPage($item);
        $this->flashArticleAutosaveSuccess($item);

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Update an existing article
     *
     * @return \Illuminate\Http\JsonResponse|RedirectResponse
     * @throws AuthenticationException
     * @throws \Throwable
     */
    protected function update()
    {
        $user = backpack_user();
        $article = $this->findArticleForAccessCheck($this->getCurrentArticleIdFromRoute());
        abort_unless($user && $this->canUseArticleEntryOperation($user, $article, CrudOperation::UPDATE), 403);
        $this->prepareArticleAuthorEditorFieldsForSave($user, $article);

        $validated = $this->crud->validateRequest()->array();

        $this->crud->setSaveAction();

        $this->traitUpdate();

        /** @var Article $item */
        $item = $this->crud->getCurrentEntry();
        $this->syncArticleSitesFromCategory($item);
        $this->syncArticleEditorsAfterUpdate($item, $user);

        $this->syncBreakingNewsMarker($item);

        $translations = $this->contentProcess($validated);

        $contentActivityLogger = app(ArticleContentActivityLogger::class);
        $changedContentFieldsByLocale = $contentActivityLogger->changedFieldsByLocale($item, $translations);

        $this->saveTranslation($item, $translations);
        $contentActivityLogger->recordUpdated($item, $changedContentFieldsByLocale);
        $this->saveSeo($item, $validated['seo'] ?? []);
        $this->saveThumbnailToArticle($item->id);
        $this->saveArticleMeta($item);
        app(ArticleCacheService::class)->forgetPage($item);
        $this->flashArticleAutosaveSuccess($item);

        return $this->crud->performSaveAction($item->getKey());
    }

    private function flashArticleAutosaveSuccess(Article $article): void
    {
        $markers = [
            (string) $article->id => 'success',
        ];

        $autosaveKey = request()->input('_article_autosave_key');
        if (is_string($autosaveKey) && trim($autosaveKey) !== '') {
            $markers[trim($autosaveKey)] = 'success';
        }

        session()->flash('article_autosave_success', $markers);
    }

    public function reserve(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('create');

        $article = new Article();
        $article->type_id = $this->typeId;
        $article->status = Article::STATUS_DRAFT;
        $article->save();

        $user = backpack_user();
        if ($user) {
            $article->authors()->syncWithoutDetaching([$user->getKey()]);
        }

        return redirect()->to($this->crud->route.'/'.$article->getKey().'/edit');
    }

    public function invalidateStatic(string $type, int $id, StaticInvalidateService $staticInvalidateService)
    {
        $article = Article::query()
            ->where('type_id', $this->typeId)
            ->findOrFail($id);

        abort_unless($this->canUseArticleEntryOperation(backpack_user(), $article, CrudOperation::INVALIDATE), 403);

        $staticInvalidateService->invalidateArticle($article);

        return response()->json([
            'message' => __('Static files invalidated'),
        ]);
    }

    /**
     * Видаляє статтю після перевірки права delete-any, delete-own або delete-limited.
     *
     * Для delete-limited ID авторів зберігаються до видалення, оскільки після нього
     * зв'язок authors вже недоступний. Після успішного видалення для кожного автора
     * створюється Redis-ліміт на 24 години для поточного користувача.
     */
    public function destroy($id, ArticleLifecycleService $articleLifecycleService)
    {
        $user = backpack_user();
        $articleId = $this->getCurrentArticleIdFromRoute() ?? $id;
        $article = $this->findArticleForAccessCheck($articleId);
        abort_unless($user && $this->canUseArticleEntryOperation($user, $article, CrudOperation::DELETE), 403);

        $deleteLimit = app(ArticleDeleteLimitService::class);
        $limitedAuthorIds = $this->shouldRecordLimitedArticleDelete($user, $article)
            ? $deleteLimit->authorIds($article)
            : [];

        $deleted = $articleLifecycleService->delete($article);

        if ($deleted && ! empty($limitedAuthorIds)) {
            $deleteLimit->recordDeleteForAuthorIds($user, $limitedAuthorIds);
        }

        return (string) $deleted;
    }

    public function bulkDelete(ArticleLifecycleService $articleLifecycleService)
    {
        abort_unless(is_backpack_admin(), 403);

        $entries = request()->input('entries', []);

        if (! is_array($entries)) {
            return response()->json([
                'message' => __('article.bulk_delete.invalid_selection'),
            ], 422);
        }

        $ids = collect($entries)
            ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT))
            ->filter(fn ($id) => $id !== false && $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'message' => __('article.bulk_delete.empty_selection'),
            ], 422);
        }

        if ($ids->count() > self::BULK_DELETE_LIMIT) {
            return response()->json([
                'message' => __('article.bulk_delete.limit_exceeded', [
                    'limit' => self::BULK_DELETE_LIMIT,
                ]),
            ], 422);
        }

        $deleted = 0;

        Article::query()
            ->where('type_id', $this->typeId)
            ->whereIn('id', $ids->all())
            ->get()
            ->each(function (Article $article) use ($articleLifecycleService, &$deleted) {
                if ($articleLifecycleService->delete($article)) {
                    $deleted++;
                }
            });

        return response()->json([
            'deleted' => $deleted,
            'requested' => $ids->count(),
            'message' => __('article.bulk_delete.success', ['count' => $deleted]),
        ]);
    }

    public function restore(string $type, $id, ArticleLifecycleService $articleLifecycleService): RedirectResponse
    {
        abort_unless(is_backpack_admin(), 403);

        $article = Article::withoutGlobalScope(CurrentSiteScope::class)
            ->onlyTrashed()
            ->where('type_id', $this->typeId)
            ->findOrFail($id);

        $articleLifecycleService->restore($article);

        \Alert::success(__('article.deleted.restored'))->flash();

        return redirect()->back();
    }


    /**
     *  One cover for all translations
     *
     * @param int $articleId
     * @return void
     * @throws AuthenticationException
     * @throws \Throwable
     */
    private function saveThumbnailToArticle(int $articleId): void
    {
        $request = request();

        if (!$request->has('thumbnail')) {
            return;
        }

        $article = Article::query()
            ->with('thumbnailAttachment')
            ->find($articleId);

        if (!$article) {
            return;
        }

        try {

            $newThumbnailId = $this->extractGalleryAttachmentId(
                $request->input('thumbnail')
            );

            DB::transaction(function () use (
                $article,
                $newThumbnailId
            ) {

                $currentThumbnailId = $article
                    ->thumbnailAttachment
                    ?->first()
                    ?->id;

                // thumbnail removed
                if (!$newThumbnailId) {

                    if ($currentThumbnailId) {

                        $article
                            ->thumbnailAttachment()
                            ->detach();
                    }

                    return;
                }

                // thumbnail unchanged
                if ($currentThumbnailId === $newThumbnailId) {
                    return;
                }

                $gallery = $this->extractGalleryData(
                    request()->input('thumbnail')
                );

                if (!$gallery) {
                    return;
                }

                $newThumbnailId = (int) $gallery['attachment_id'];

                // replace thumbnail
                $article
                    ->thumbnailAttachment()
                    ->detach();

                $article
                    ->thumbnailAttachment()
                    ->attach(
                        $newThumbnailId,
                        [
                            'type' => Attachment::THUMBNAIL_TYPE,
                            'order' => 0,
                        ]
                    );
            });

        } catch (\Throwable $e) {

            Log::error('Error saving thumbnail to article', [
                'article_id' => $articleId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FIELDS & COLUMNS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the list fields
     *
     * @return void
     */
    private function getListFields()
    {
        CRUD::addColumn([
            'name'  => 'sites',
            'label' => __('article.fields.sites'),
            'type'  => 'model_function',
            'function_name' => 'getSiteName',
            'escaped' => false,
            'limit' => 100,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('sites', function ($siteQuery) use ($searchTerm) {
                    $siteQuery->where('name', 'like', "{$searchTerm}%");
                });
            },
        ]);
        CRUD::addColumn([
            'name'  => 'category_full',
            'label' => __('article.fields.category_full'),
            'type'  => 'model_function',
            'function_name' => 'getCategoryFull',
            'escaped' => false,
            'limit' => 100,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                    $categoryQuery
                        ->whereHas('translations', function ($translationQuery) use ($searchTerm) {
                            $translationQuery->where('name', 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('parent.translations', function ($translationQuery) use ($searchTerm) {
                            $translationQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            },
        ]);
        CRUD::addColumn([
            'name'  => 'title',
            'label' => __('article-translate.fields.title'),
            'type'  => 'model_function',
            'function_name' => 'getTitles',
            'escaped' => false,
            'limit' => 3000,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $articleIds = [];

                try {
                    $articleIds = app(AdminArticleTitleSearchService::class)
                        ->findArticleIds($searchTerm, $this->typeId);
                } catch (AdminArticleTitleSearchException) {
                    \Alert::error(__('admin.search.article_title_failed'))->flash();
                }

                $query->orWhereIn('articles.id', $articleIds);
            },
        ]);
        CRUD::addColumn([
            'name'  => 'authors',
            'label' => __('article.fields.authors'),
            'type'  => 'model_function',
            'function_name' => 'getAuthors',
            'escaped' => false,
            'limit' => 100,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('authors', function ($authorQuery) use ($searchTerm) {
                    $authorQuery->where('name', 'like', "%{$searchTerm}%");
                });
            },
        ]);
        CRUD::addColumn([
            'name'  => 'chart',
            'label' => __('article.fields.chart'),
            'type'  => 'view',
            'view'  => 'admin.charts.article-chart',
        ]);
        CRUD::addColumn([
            'name'  => 'editors',
            'label' => __('article.fields.editors'),
            'type'  => 'model_function',
            'function_name' => 'getEditors',
            'escaped' => false,
            'limit' => 100,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('editors', function ($editorQuery) use ($searchTerm) {
                    $editorQuery->where('name', 'like', "%{$searchTerm}%");
                });
            },
        ]);
        CRUD::addColumn([
            'name' => 'status',
            'label' => __('article.fields.status'),
            'type'  => 'model_function',
            'function_name' => 'getStatusIcon',
            'escaped' => false,
            'limit' => 1000,
        ]);
        CRUD::addColumn([
            'name'  => 'published_at',
            'label' => __('article.fields.published_at'),
            'type'  => 'view',
            'view'  => 'admin.blocks.date_split',
        ]);
//        CRUD::addColumn([
//            'name'  => 'source_url',
//            'label' => __('article.fields.source_url'),
//            'type'  => 'url',
//            'limit' => 50,
//        ]);
    }

    /**
     * Get the create or update fields
     *
     * @return void
     */
    private function getCreateOrUpdateFields()
    {
        $currentTypeId = $this->getCurrentArticleTypeIdForFields();

        // Site
        CRUD::addField([
            'name'    => 'separator_sites',
            'type'    => 'custom_html',
            'value'   => view('admin.shared.separator', ['title' => __('article.separators.main')])->render(),
            'tab' => __('admin.tabs.general'),
        ]);
//        $this->addConfiguredField('site_id', [
//            'name'    => 'sites',
//            'label'   => __('article.fields.sites'),
//            'type'    => 'select2_multiple',
//            'entity'  => 'sites',
//            'attribute' => 'name',
//            'model'   => Site::class,
//            'wrapper'=> ['class' => 'form-group col-md-6'],
//            'tab' => __('admin.tabs.general'),
//        ], $currentTypeId);
        $this->addConfiguredField('published_at', [
            'name'    => 'published_at',
            'label'   => __('article.fields.published_at'),
            'type'    => 'datetime',
            'wrapper'=> ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
        ], $currentTypeId);

        // Category and tags
        CRUD::addField([
            'name'    => 'separator_category',
            'type'    => 'custom_html',
            'value'   => view('admin.shared.separator', ['title' => __('article.separators.category')])->render(),
            'tab' => __('admin.tabs.general'),
        ]);
        $this->addConfiguredField('category_id', [
            'name'    => 'category_id',
            'label'   => __('article.fields.category_id'),
            'type'    => 'select2',
            'entity'  => 'category',
            'attribute' => 'display_name',
            'model'   => Category::class,
            'allows_null' => true,
            'options' => function ($query) {
                return $query->whereNull('parent_id')->get();
            },
            'wrapper'=> ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
        ], $currentTypeId);
        // CRUD::addField([
        //     'name'            => 'category_id',
        //     'label'           => __('article.fields.sub_category'),
        //     'type'            => 'select2_from_ajax',
        //     'entity'          => 'category',
        //     'attribute'       => 'slug',
        //     'data_source'     => backpack_url('category/fetch-children'),
        //     'placeholder'     => __('article.fields.sub_category'),
        //     'minimum_input_length' => 0,
        //     'dependencies'    => ['parent_category'],
        //     'include_all_form_fields' => true,
        //     'wrapper'         => ['class' => 'form-group col-md-6'],
        //     'tab'             => __('admin.tabs.general'),
        // ]);
        $this->addConfiguredField('tags', [
            'name'    => 'tags',
            'label'   => __('article.fields.tags'),
            'type'    => 'select2_from_ajax_multiple',
            'entity'  => 'tags',
            'attribute' => 'display_name',
            'model'   => Tag::class,
            'pivot'   => true,
            'data_source' => route('api.tags.fetch', [
                'type' => $this->type,
                'locale' => app()->getLocale(),
            ]),
            'method' => 'GET',
            'include_all_form_fields' => false,
            'placeholder' => __('article.fields.tags'),
            'minimum_input_length' => 3,
            'wrapper'=> ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
        ], $currentTypeId);
        $this->addConfiguredField('markers', [
            'name'    => 'markers',
            'label'   => __('article.fields.markers'),
            'type'    => 'select2_multiple',
            'entity'  => 'markers',
            'attribute' => 'display_name',
            'model'   => Marker::class,
            'pivot'   => true,
            'wrapper'=> ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
        ], $currentTypeId);

        $breakingNews = app(BreakingNews::class);
        $breakingNewsMarkerId = $breakingNews->markerId();

        if ($breakingNewsMarkerId !== null && $breakingNews->supportsTypeCode($this->type)) {
            CRUD::addField([
                'name' => 'breaking_news_switch',
                'type' => 'custom_html',
                'value' => view('admin.articles.breaking-news', [
                    'enabled' => $this->isBreakingNewsEnabledForForm(),
                    'markerId' => $breakingNewsMarkerId,
                ])->render(),
                'wrapper' => ['class' => 'form-group col-md-6 d-flex align-items-end'],
                'tab' => __('admin.tabs.general'),
            ]);
        }

        $this->addConfiguredField('do_follow', [
            'name' => 'do_follow',
            'label' => __('article.fields.do_follow'),
            'type' => 'switch',
            'default' => false,
            'wrapper' => ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
            'color' => '#198754',
        ], $currentTypeId);

        // Authors
        if (backpack_user() && $this->canManageArticleAuthors(backpack_user())) {
            CRUD::addField([
                'name'    => 'separator_authors',
                'type'    => 'custom_html',
                'value'   => view('admin.shared.separator', ['title' => __('article.separators.authors_editors')])->render(),
                'tab' => __('admin.tabs.general'),
            ]);
            $this->addConfiguredField('authors', [
                'name'    => 'authors',
                'label'   => __('article.fields.authors'),
                'type'    => 'select2_multiple',
                'entity'  => 'authors',
                'attribute' => 'fullname',
                'model'   => User::class,
                'options' => fn ($query) => $this->getArticleUserOptions($query, 'authors'),
                'wrapper'=> ['class' => 'form-group col-md-6'],
                'tab' => __('admin.tabs.general'),
            ], $currentTypeId);
            $this->addConfiguredField('editors', [
                'name'    => 'editors',
                'label'   => __('article.fields.editors'),
                'type'    => 'select2_multiple',
                'entity'  => 'editors',
                'attribute' => 'fullname',
                'model'   => User::class,
                'options' => fn ($query) => $this->getArticleUserOptions($query, 'editors'),
                'wrapper'=> ['class' => 'form-group col-md-6'],
                'tab' => __('admin.tabs.general'),
            ], $currentTypeId);
        }

        // Type and status
        CRUD::addField([
            'name'    => 'separator_type_status',
            'type'    => 'custom_html',
            'value'   => view('admin.shared.separator', ['title' => __('article.separators.type_status')])->render(),
            'tab' => __('admin.tabs.general'),
        ]);

        CRUD::addField([
            'name' => 'type_id',
            'type' => 'hidden',
            'value' => $currentTypeId,
            'tab' => __('admin.tabs.general'),
        ]);
        $this->addConfiguredField('status', [
            'name'    => 'status',
            'label'   => __('article.fields.status'),
            'type'    => 'select_from_array',
            'options' => Article::getStatusOptions(),
            'wrapper'=> ['class' => 'form-group col-md-6'],
            'tab' => __('admin.tabs.general'),
        ], $currentTypeId);

        // Source URL and Media
        CRUD::addField([
            'name'    => 'separator_source',
            'type'    => 'custom_html',
            'value'   => view('admin.shared.separator', ['title' => __('article.separators.source')])->render(),
            'tab' => __('admin.tabs.general'),
        ]);
        $this->addConfiguredField('source_url', [
            'name'    => 'source_url',
            'label'   => __('article.fields.source_url'),
            'type'    => 'url',
            'hint'     => __('article.hints.source_url'),
            'wrapper'=> ['class' => 'form-group col-md-12'],
            'tab' => __('admin.tabs.general'),
        ], $currentTypeId);

        $this->addTypeSpecificMetaFields();

        CRUD::addField([
            'name' => 'cover',
            'type' => 'custom_html',
            'value' => view('admin.cover.cover', [
                'article' => $this->crud->getOperation() === CrudOperation::UPDATE
                    ? $this->crud->getCurrentEntry()
                    : new Article(),

                'fieldName' => 'thumbnail',

                'fieldConfig' => [
                    'label' => __('article.fields.thumbnail'),
                    'type' => 'gallery',
                    'options' => [
                        'gallery_only' => true,
                    ],
                ],

                'prefix' => '',
            ])->render(),

            'tab' => __('article.fields.thumbnail'),
        ]);

        CRUD::addField([
            'name'    => 'gallery_modal',
            'type'    => 'custom_html',
            'value'   => view('admin.shared.gallery-modal')->render(),
        ]);
    }

    private function getTypeIdFromRoute(): ?int
    {
        $code = request()->route('type');

        if (! $code) {
            return null;
        }

        return ArticleType::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->value('id');
    }

    private function getCurrentArticleIdFromRoute(): ?int
    {
        $id = request()->route('id') ?? request()->route('article');

        return is_numeric($id) ? (int) $id : null;
    }

    private function shouldRecordLimitedArticleDelete(User $user, Article $article): bool
    {
        return ! $this->canUseArticleAnyOperation($user, CrudOperation::DELETE)
            && ! ($this->canUseArticleOwnOperation($user, CrudOperation::DELETE) && $this->isArticleAuthor($user, $article))
            && $this->canUseArticleLimitedDeleteOperation($user);
    }

    private function getTypeIdForCurrentEntry(): ?int
    {
        $entry = $this->crud->getCurrentEntry();
        if ($entry instanceof Article && $entry->type_id) {
            return (int) $entry->type_id;
        }

        $articleId = (int) (request()->route('id') ?? request()->route('article') ?? 0);
        if ($articleId <= 0) {
            return null;
        }

        return (int) Article::query()->whereKey($articleId)->value('type_id');
    }

    private function applyTypeUiContext(?int $typeId): void
    {
        if (! $typeId || $typeId <= 0) {
            return;
        }

        $typeName = $this->getArticleTypeDisplayName($typeId);
        if ($typeName === null || $typeName === '') {
            return;
        }

        CRUD::setEntityNameStrings($typeName, $typeName);

        $this->crud->setHeading($typeName);

        $action = $this->crud->getActionMethod();
        if ($action === CrudOperation::CREATE) {
            $this->crud->setSubheading(trans('backpack::crud.add') . ' ' . $typeName . '.');
            $this->crud->setTitle(trans('backpack::crud.add') . ' ' . $typeName);
        } elseif ($action === 'edit' || $action === CrudOperation::UPDATE) {
            $this->crud->setSubheading(trans('backpack::crud.edit') . ' ' . $typeName . '.');
            $this->crud->setTitle(trans('backpack::crud.edit') . ' ' . $typeName);
        } elseif ($action === CrudOperation::SHOW) {
            $this->crud->setSubheading($typeName);
            $this->crud->setTitle($typeName);
        } else {
            $this->crud->setSubheading('');
            $this->crud->setTitle($typeName);
        }
    }

    private function getArticleTypeDisplayName(int $typeId): ?string
    {
        if (array_key_exists($typeId, $this->typeDisplayNameMemo)) {
            return $this->typeDisplayNameMemo[$typeId];
        }

        $type = ArticleType::query()->with('translations')->find($typeId);

        return $this->typeDisplayNameMemo[$typeId] = $type?->display_name;
    }

    private function addTypeSpecificMetaFields(): void
    {
//        $typeId = $this->getCurrentArticleTypeIdForFields();
        $typeId = $this->getTypeIdFromRoute();

        if ($typeId === null) {
            return;
        }

        if ($this->isTypeByCode($typeId, 'video')) {
            $this->addVideoMetaFields();
        }

        if ($this->isTypeByCode($typeId, 'kompaniyi')) {
            if ($this->isAnyCompanyFieldVisibleForType($typeId)) {
                $this->addCompanyMetaFields($typeId);
            }
        }

        if ($this->isTypeByCode($typeId, 'page')) {
            if ($this->isFieldVisibleForType('page_role', $typeId)) {
                $this->addPageMetaFields();
            }
        }
    }

    private function addVideoMetaFields(): void
    {
        $update = $this->crud->getCurrentOperation() === CrudOperation::UPDATE;

        CRUD::addField([
            'name'  => 'youtube_id',
            'label' => __('article.fields.youtube_id'),
            'type'  => 'text',
            'value' => $update ? $this->getGlobalMetaValue('youtube_id') : '',
            'hint' => __('article.hints.youtube_id'),
            'wrapper' => ['class' => 'form-group col-md-12'],
            'tab'   => __('admin.tabs.general'),
        ]);
    }

    private function addPageMetaFields(): void
    {
        $update = $this->crud->getCurrentOperation() === CrudOperation::UPDATE;

        CRUD::addField([
            'name'  => 'page_role',
            'label' => __('article.fields.page_role'),
            'type'  => 'text',
            'value' => $update ? $this->getMetaValue('page_role') : '',
            'hint'  => __('article.hints.page_role'),
            'wrapper' => ['class' => 'form-group col-md-12'],
            'tab'   => __('admin.tabs.general'),
        ]);
    }

    private function addCompanyMetaFields(int $typeId): void
    {
        $labels = ArticleMeta::companyMetaLabels();

        foreach (ArticleMeta::companyMetaRequestFields() as $dbField => $requestField) {
            if (! $this->isFieldVisibleForType($requestField, $typeId)) {
                continue;
            }

            $field = [
                'name' => $requestField,
                'label' => $labels[$dbField] ?? $dbField,
                'type' => 'text',
                'value' => $this->getMetaValue($dbField),
                'wrapper' => ['class' => 'form-group col-md-6'],
                'tab' => __('admin.tabs.general'),
            ];

            if ($dbField === 'company_type') {
                $options = [];
                foreach (ArticleMeta::companyTypeOptions() as $opt) {
                    $options[$opt['value']] = $opt['label'];
                }

                $field['type'] = 'select_from_array';
                $field['options'] = $options;
                $field['allows_null'] = true;
            }

            CRUD::addField($field);
        }
    }

    private function addAuditHistoryField(int $articleId): void
    {
        if ($articleId <= 0) {
            return;
        }

        CRUD::addField([
            'name' => 'audit_history',
            'type' => 'custom_html',
            'value' => view('admin.audit.article-history', [
                'rows' => app(ArticleAuditHistoryService::class)->rows($articleId),
            ])->render(),
            'tab' => __('audit.history_title'),
        ]);
    }


    private function saveArticleMeta(Article $article): void
    {
        $typeId = (int) $article->type_id;
        if ($typeId <= 0) {
            return;
        }

        $locale = (string) (app()->getLocale() ?: 'uk');

        if ($this->isTypeByCode($typeId, 'video')) {
            $youtubeId = YouTubeVideoResolver::idFromUrlOrId(request()->input('youtube_id'));
            $this->upsertGlobalMetaValue($article->id, 'youtube_id', $youtubeId);
        }

        if ($this->isTypeByCode($typeId, 'kompaniyi')) {
            foreach (ArticleMeta::companyMetaRequestFields() as $dbField => $requestField) {
                $this->upsertMetaValue($article->id, $locale, $dbField, request()->input($requestField));
            }
        }

        if ($this->isTypeByCode($typeId, 'page')) {
            $this->upsertMetaValue($article->id, $locale, 'page_role', request()->input('page_role'));
        }
    }

    private function upsertMetaValue(int $articleId, string $locale, string $field, mixed $value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === null || $value === '') {
            ArticleMeta::query()
                ->where('article_id', $articleId)
                ->where('locale', $locale)
                ->where('field', $field)
                ->delete();

            return;
        }

        ArticleMeta::query()->updateOrCreate(
            ['article_id' => $articleId, 'locale' => $locale, 'field' => $field],
            ['value' => (string) $value]
        );
    }

    private function upsertGlobalMetaValue(int $articleId, string $field, mixed $value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        $query = ArticleMeta::query()
            ->where('article_id', $articleId)
            ->where('field', $field);

        if ($value === null || $value === '') {
            $query->delete();

            return;
        }

        $query->whereNotNull('locale')->delete();

        ArticleMeta::query()->updateOrCreate(
            ['article_id' => $articleId, 'locale' => null, 'field' => $field],
            ['value' => (string) $value],
        );
    }

    private function getMetaValue(string $field): ?string
    {
        $entry = $this->crud->getCurrentEntry();
        if (! $entry instanceof Article) {
            return null;
        }

        if (! $entry->relationLoaded('meta')) {
            $entry->load('meta');
        }

        $locale = (string) (app()->getLocale() ?: 'uk');

        $meta = $entry->meta
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        if ($meta) {
            return $meta->value;
        }

        $metaUk = $entry->meta
            ->where('locale', 'uk')
            ->where('field', $field)
            ->first();

        return $metaUk?->value;
    }

    private function getGlobalMetaValue(string $field): ?string
    {
        $entry = $this->crud->getCurrentEntry();
        if (! $entry instanceof Article) {
            return null;
        }

        if (! $entry->relationLoaded('meta')) {
            $entry->load('meta');
        }

        return $entry->meta
            ->where('locale', null)
            ->where('field', $field)
            ->first()
            ?->value;
    }

    private function getCurrentArticleTypeIdForFields(): ?int
    {
        if ($this->crud->getCurrentOperation() === CrudOperation::CREATE) {
            return $this->getTypeIdFromRoute();
        }

        $entry = $this->crud->getCurrentEntry();
        if ($entry instanceof Article && $entry->type_id) {
            return (int) $entry->type_id;
        }

        $articleId = (int) (request()->route('id') ?? request()->route('article') ?? 0);
        if ($articleId <= 0) {
            return null;
        }

        return (int) Article::query()->whereKey($articleId)->value('type_id');
    }

    private function isTypeByCode(int $typeId, string $code): bool
    {
        if (! array_key_exists($code, $this->typeIdByCodeMemo)) {
            $this->typeIdByCodeMemo[$code] = (int) ArticleType::query()->where('code', $code)->value('id');
        }

        return $this->typeIdByCodeMemo[$code] > 0 && $this->typeIdByCodeMemo[$code] === $typeId;
    }

    private function isAnyCompanyFieldVisibleForType(int $typeId): bool
    {
        foreach (ArticleMeta::companyMetaRequestFields() as $_dbField => $requestField) {
            if ($this->isFieldVisibleForType($requestField, $typeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Synchronizes sites implied by the article's selected category.
     */
    private function syncArticleSitesFromCategory(Article $article): void
    {
        if (! $article->category_id) {
            return;
        }

        $category = Category::query()->find($article->category_id);

        if (! $category) {
            return;
        }

        $siteIds = array_filter([
            $category->site_id,
            filled($category->slug)
                ? Site::query()->where('slug', $category->slug)->value('id')
                : null,
        ]);

        $article->sites()->sync(array_unique($siteIds));
    }

    private function isFieldVisibleForType(string $fieldName, ?int $typeId): bool
    {
        $config = $this->getFieldConfigurationForType($fieldName, $typeId);
        if (! $config instanceof ArticleFieldConfiguration) {
            return true;
        }

        return (bool) $config->is_visible;
    }

    /**
     * Додає або прибирає маркер срочної новини відповідно до даних форми.
     */
    private function syncBreakingNewsMarker(Article $article): void
    {
        $breakingNews = app(BreakingNews::class);

        if (! $breakingNews->supportsTypeId((int) $article->type_id)) {
            return;
        }

        $markerId = $breakingNews->markerId();

        if ($markerId === null) {
            return;
        }

        $enabled = request()->boolean('breaking_news')
            || in_array(
                $markerId,
                array_map('intval', (array) request()->input('markers', [])),
                true,
            );

        if ($enabled) {
            $article->markers()->syncWithoutDetaching([$markerId]);

            return;
        }

        $article->markers()->detach($markerId);
    }

    /**
     * Визначає початковий стан перемикача срочної новини у формі.
     */
    private function isBreakingNewsEnabledForForm(): bool
    {
        if (old('breaking_news') !== null) {
            return filter_var(old('breaking_news'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->crud->getOperation() !== CrudOperation::UPDATE) {
            return false;
        }

        $entry = $this->crud->getCurrentEntry();

        $markerId = app(BreakingNews::class)->markerId();

        return $markerId !== null
            && $entry instanceof Article
            && $entry->markers()->whereKey($markerId)->exists();
    }

    /**
     * @param string $fieldName
     * @param int|null $typeId
     * @return ArticleFieldConfiguration|null
     */
    private function getFieldConfigurationForType(string $fieldName, ?int $typeId): ?ArticleFieldConfiguration
    {
        return app(ArticleFieldConfigurationResolver::class)
            ->forType($typeId)
            ->get($fieldName);
    }

    /**
     * @param string $fieldName
     * @param array $definition
     * @param int|null $typeId
     * @return void
     */
    private function addConfiguredField(string $fieldName, array $definition, ?int $typeId): void
    {
        if (! $this->isFieldVisibleForType($fieldName, $typeId)) {
            return;
        }

        CRUD::addField($definition);
    }

    /**
     * Returns active users and preserves already assigned soft-deleted users in edit forms.
     */
    private function getArticleUserOptions($query, string $relation)
    {
        $article = $this->crud->getCurrentOperation() === CrudOperation::UPDATE
            ? $this->crud->getCurrentEntry()
            : null;

        $selectedUserIds = $article instanceof Article
            ? $article->{$relation}()->withTrashed()->pluck('users.id')->all()
            : [];

        return $query
            ->withTrashed()
            ->where(function ($userQuery) use ($selectedUserIds) {
                $userQuery->whereNull('users.deleted_at');

                if ($selectedUserIds !== []) {
                    $userQuery->orWhereIn('users.id', $selectedUserIds);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param string|null $json
     * @return array|null
     * @throws AuthenticationException
     */
    private function extractGalleryData(?string $json): ?array
    {
        $attachmentId = $this->extractGalleryAttachmentId($json);

        if (!$attachmentId) {
            return null;
        }

        $user = backpack_user();

        if (!$user) {
            throw new AuthenticationException();
        }

        $attachment = Attachment::parents()
            ->whereKey($attachmentId)
            ->where('user_id', $user->id)
            ->first();

        if (!$attachment) {
            throw new NotFoundHttpException();
        }

        return [
            'attachment_id' => $attachment->id,
            'url'           => $attachment->url,
        ];
    }

    private function extractGalleryAttachmentId(?string $json): ?int
    {
        if ($json === null || $json === '') {
            return null;
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return null;
        }

        $galleryBlock = collect($data['blocks'] ?? [])
            ->firstWhere('type', 'gallery');

        if (!$galleryBlock) {
            return null;
        }

        $attachmentId = data_get($galleryBlock, 'data.attachment_id')
            ?: data_get($galleryBlock, 'data.images.0.attachment_id');

        if (!$attachmentId) {
            return null;
        }

        return (int) $attachmentId;
    }

    /**
     * @param array $validated
     * @return array
     */
    private function contentProcess(array $validated): array
    {
        $translations = $validated['translations'];
        $doFollow = (bool)$validated['do_follow'];

        if (empty($translations)) {
            return [];
        }

        foreach ($translations as $locale => $translation) {

            $content = $translation['content'] ?? null;

            if ($content !== null) {

                $translations[$locale]['content'] = app(EditorContentProcessor::class)
                    ->process(
                        $content,
                        [
                            'do_follow' => $doFollow,
                            'target_blank' => true,
                            'operation' => EditorService::SAVE_ACTION,
                        ]
                    );
            }
        }

        return $translations;
    }

}
