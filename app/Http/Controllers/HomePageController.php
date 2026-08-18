<?php

namespace App\Http\Controllers;

use App\Facades\SchemaGraph;
use App\Facades\Seo;
use App\Models\Articles\ArticlesBlockSetting;
use App\Models\Articles\Category;
use App\Repositories\ArticleRepository;
use App\Repositories\Interfaces\ArticleTypeRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\SEO\Pages\CategorySeo;
use App\SEO\Pages\HomeSeo;
use App\SEO\Schemas\BreadcrumbSchema;
use App\Services\Breadcrumbs\BreadcrumbsService;
use App\View\PageBuilders\HomePageBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HomePageController extends Controller
{

    public function __construct(
        protected CategoryRepositoryInterface $categories,
        protected ArticleTypeRepositoryInterface $types,
        private readonly BreadcrumbsService $breadcrumbsService,
        protected ArticleRepository $articleRepository
    ) {}

    /**
     * Display the homepage
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function homepage(Request $request, HomePageBuilder $builder)
    {
        $domain = $request->route('domain') ?? $request->getHost();
        $category = $this->categoryForDomain($domain);

        if ($category) {
            return $this->category($category, $this->articleRepository);
        }

        $page = $builder->build();

        Seo::set(HomeSeo::make());

        return view('homepage', compact('page'));
    }


    public function category(Category $category, ArticleRepository $articleRepository)
    {
        /** @var LengthAwarePaginator $articles */
        $articles = $articleRepository->getPublishedByCategory(
            $category->id,
            app()->getLocale(),
            config('article.article.per_page')
        );

        $breadcrumbs = $this->breadcrumbsService->forCategory($category);
        SchemaGraph::add(BreadcrumbSchema::make(
            $this->breadcrumbsService->toSchemaItems($breadcrumbs, url()->current())
        ));

        Seo::set(CategorySeo::make($category))->paginate($articles);

        return view('category', [
            'category' => $category,
            'page' => [
                'swiper' => [
                    'articles' => $articles,
                ],
                'articles' => [
                    'leftArticles'     => $articles,  //todo the same content in both blocks ?
                    'leftArticlesCode' => ArticlesBlockSetting::ARTICLES_CONTAINER_LEFT,   //TODO what type should use ?
                    'rightArticles'    => $articles,
                    'rightArticlesCode' => ArticlesBlockSetting::ARTICLES_CONTAINER_RIGHT,
                ]
            ],
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    private function categoryForDomain(string $domain): ?Category
    {
        [$slug, $siteDomain] = array_pad(explode('.', $domain, 2), 2, null);

        if (! $slug || ! $siteDomain) {
            return null;
        }

        return Category::query()
            ->where('slug', $slug)
            ->where('subdomain', true)
            ->whereHas('site', fn ($query) => $query->where('domain', $siteDomain))
            ->with('translations')
            ->first();
    }
}
