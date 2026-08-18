<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Exceptions\AdminArticleTitleSearchException;
use App\Models\Articles\Article;
use App\Services\Article\AdminArticleTitleSearchService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Database\Eloquent\Builder;

class PublicationHistoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        $user = backpack_user();

        abort_unless($user, 403);

        CRUD::setModel(Article::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/publication-history');
        CRUD::setEntityNameStrings(
            __('admin.publication_history.title_in_singular'),
            __('admin.publication_history.title_in_plural')
        );

        $this->crud->query
            ->published()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $user->created_at)
            ->whereHas('authors', function (Builder $query) use ($user) {
                $query->where('users.id', $user->getKey());
            });
    }

    protected function setupListOperation(): void
    {
        $this->crud->setDefaultPageLength(25);
        $this->crud->setPageLengthMenu([10, 25, 50, 100]);
        $this->crud->query->with([
            'authors',
            'category.parent',
            'sites',
            'translations',
            'type.translations',
        ]);

        CRUD::orderBy('published_at', 'desc');
        CRUD::orderBy('id', 'desc');

        $this->addColumns();
    }

    protected function setupShowOperation(): void
    {
        $this->crud->query->with([
            'authors',
            'category.parent',
            'sites',
            'translations',
            'type.translations',
        ]);

        $this->addColumns();
    }

    private function addColumns(): void
    {
        CRUD::addColumn([
            'name' => 'published_at',
            'label' => __('article.fields.published_at'),
            'type' => 'view',
            'view' => 'admin.blocks.date_split',
        ]);

        CRUD::addColumn([
            'name' => 'type',
            'label' => __('article.fields.type'),
            'type' => 'closure',
            'function' => fn (Article $entry) => e($entry->type?->display_name ?? '-'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('type.translations', function ($translationQuery) use ($searchTerm) {
                    $translationQuery->where('name', 'like', "{$searchTerm}%");
                })->orWhereHas('type', function ($typeQuery) use ($searchTerm) {
                    $typeQuery->where('code', 'like', "%{$searchTerm}%");
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'category_full',
            'label' => __('article.fields.category_full'),
            'type' => 'model_function',
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
            'name' => 'title',
            'label' => __('article-translate.fields.title'),
            'type' => 'model_function',
            'function_name' => 'getTitles',
            'escaped' => false,
            'limit' => 2000,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $articleIds = [];

                try {
                    $articleIds = app(AdminArticleTitleSearchService::class)
                        ->findArticleIds($searchTerm);
                } catch (AdminArticleTitleSearchException) {
                    \Alert::error(__('admin.search.article_title_failed'))->flash();
                }

                $query->orWhereIn('articles.id', $articleIds);
            },
        ]);

        CRUD::addColumn([
            'name' => 'sites',
            'label' => __('article.fields.sites'),
            'type' => 'model_function',
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
            'name' => 'views',
            'label' => __('article.fields.views'),
            'type' => 'number',
        ]);
    }
}
