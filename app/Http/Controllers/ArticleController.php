<?php

namespace App\Http\Controllers;

use App\Facades\SchemaGraph;
use App\Facades\Seo;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Category;
use App\Repositories\ArticleRepository;
use App\SEO\Pages\ArticleSeo;
use App\SEO\Pages\ArticleTypeSeo;
use App\SEO\Pages\CategorySeo;
use App\SEO\Pages\PageSeo;
use App\Services\Article\RelatedArticleService;
use App\Services\Article\ArticleUrlBuilder;
use App\Services\Article\YouTubeVideoResolver;
use App\Services\Breadcrumbs\BreadcrumbsService;
use App\SEO\Schemas\ArticleSchema;
use App\SEO\Schemas\BreadcrumbSchema;
use App\Support\LanguageSwitcherStore;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class ArticleController extends Controller
{
    public function __construct(
        private readonly BreadcrumbsService $breadcrumbsService,
        private readonly RelatedArticleService $relatedArticleService,
        private readonly ArticleUrlBuilder $articleUrlBuilder,
    ) {
    }

    public function index(): \Illuminate\Http\Response|\Illuminate\Contracts\View\View
    {
        $typeId = ArticleType::allCached()->where('code', ArticleType::NEWS)->value('id');

        $article = Article::forSignificantList($typeId, null, null)->first();

        $title = $article?->title_with_markers ?? $article?->title ?? '';
        $readMoreArticle = $article ? $this->relatedArticleService->previousRelated($article) : null;
        [$readMoreUrl, $readMoreTitle] = $this->readMoreData($readMoreArticle);

        return view('article', [
            'article' => $article,
            'title' => $title,
            'readMoreUrl' => $readMoreUrl,
            'readMoreTitle' => $readMoreTitle,
        ]);
    }


    /**
     * @param array $params
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Contracts\Container\CircularDependencyException
     */
    private function getArticleForShow(array $params)
    {
        $id    = $params['id'] ?? null;
        $type  = $params['type'] ?? null;
        $subcategory = $params['subcategory'] ?? null;
        $slug = $params['slug'] ?? null;
        $locale = $params['locale'] ?? app()->getLocale();

        if (!$id || !$type) {
            throw new NotFoundHttpException;
        }

        $currentSite = app('currentSite');
        $typeId = ArticleType::getTypeId($type);

        if (!$typeId && !in_array($type, ArticleType::TYPES_CAT, true)) {
            throw new NotFoundHttpException;
        }

        $query = Article::query()
            ->published()
            ->whereKey($id)
//            ->whereHas('sites', fn($q) => $q->whereKey($currentSite->id))  //todo global ?
            ->with(['type', 'category', 'translations', 'seoMeta.translations', 'sites', 'tags.translations']);

        if ($typeId) {
            $query->where('type_id', $typeId);
        } else {
            $query->whereHas('category', fn($q) => $q->where('slug', $type));
        }

        if (!empty($subcategory)) {
            $query->whereHas('category', fn($q) => $q->where('slug', $subcategory));
        }

        $article = $query->firstOrFail();

        $expectedSlug = $article->translate($locale)?->slug;

        if (!empty($slug) && !empty($expectedSlug) && $slug !== $expectedSlug) {
            return redirect()->to(
                $this->articleUrlBuilder->urlForLocale($article, $locale),
                301
            );
        }

        return $this->show(request(), $article);
    }


    /**
     * @param Request $request
     * @param Article $article
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View
     */
    public function show(Request $request, Article $article): \Illuminate\Http\Response|\Illuminate\Contracts\View\View
    {
        app(LanguageSwitcherStore::class)->set($article);

        $title = $article->title_with_markers ?? $article->title ?? '';

        SchemaGraph::add(ArticleSchema::make($article));

        $breadcrumbs = $this->breadcrumbsService->forArticle($article);
        $breadcrumbsForSchema = $this->breadcrumbsService->toSchemaItems($breadcrumbs, url()->current());

        SchemaGraph::add(BreadcrumbSchema::make($breadcrumbsForSchema));

        if ($article->type?->code === 'page') {
            Seo::set(PageSeo::make($article));

            return view('page', [
                'article'     => $article,
                'title'       => $title,
                'breadcrumbs' => $breadcrumbs,
            ]);
        }

        if ($article->type?->code === ArticleType::VIDEO) {

            $youtubeId = $article->meta()
                ->where('field', 'youtube_id')
                ->whereNull('locale')
                ->value('value');

            $videoEmbedUrl = YouTubeVideoResolver::embedUrl($youtubeId);
            abort_unless($videoEmbedUrl, 404);

            $readMoreArticle = $this->relatedArticleService->previousRelated($article);
            [$readMoreUrl, $readMoreTitle] = $this->readMoreData($readMoreArticle);

            Seo::set(ArticleSeo::make($article));

            if ($request->ajax()) {
                $articleTitle = $article->seoMeta?->translate(app()->getLocale())?->meta_title
                    ?: ($article->title_with_markers ?? $article->title ?? '');

                return response(Blade::render(
                    '<x-containers.article-container-component
                        :article="$article"
                        :with-load-point="true"
                        :article-title="$articleTitle"
                        :article-url="$articleUrl"
                        :read-more-url="$readMoreUrl"
                        :read-more-title="$readMoreTitle"
                        :video-embed-url="$videoEmbedUrl"
                        :video-thumbnail-url="$videoThumbnailUrl"
                        :video-thumbnail-fallback-url="$videoThumbnailFallbackUrl"
                    />',
                    [
                        'article' => $article,
                        'articleTitle' => $articleTitle,
                        'articleUrl' => $article->getArticleUrl(),
                        'readMoreUrl' => $readMoreUrl,
                        'readMoreTitle' => $readMoreTitle,
                        'videoEmbedUrl' => $videoEmbedUrl,
                        'videoThumbnailUrl' => YouTubeVideoResolver::thumbnailUrl($youtubeId),
                        'videoThumbnailFallbackUrl' => YouTubeVideoResolver::thumbnailFallbackUrl($youtubeId),
                    ]
                ))->header('X-Robots-Tag', 'noindex, nofollow');
            }

            return view('video', [
                'article' => $article,
                'title' => $title,
                'breadcrumbs' => $breadcrumbs,
                'videoEmbedUrl' => $videoEmbedUrl,
                'videoThumbnailUrl' => YouTubeVideoResolver::thumbnailUrl($youtubeId),
                'videoThumbnailFallbackUrl' => YouTubeVideoResolver::thumbnailFallbackUrl($youtubeId),
                'readMoreUrl' => $readMoreUrl,
                'readMoreTitle' => $readMoreTitle,
            ]);
        }

        Seo::set(ArticleSeo::make($article));

        $readMoreArticle = $this->relatedArticleService->previousRelated($article);
        [$readMoreUrl, $readMoreTitle] = $this->readMoreData($readMoreArticle);

        if ($request->ajax()) {
            $articleTitle = $article->seoMeta?->translate(app()->getLocale())?->meta_title
                ?: ($article->title_with_markers ?? $article->title ?? '');

            return response(Blade::render(
                '<x-containers.article-container-component
                    :article="$article"
                    :with-load-point="true"
                    :article-title="$articleTitle"
                    :article-url="$articleUrl"
                    :read-more-url="$readMoreUrl"
                    :read-more-title="$readMoreTitle"
                />',
                [
                    'article' => $article,
                    'articleTitle' => $articleTitle,
                    'articleUrl' => $article->getArticleUrl(),
                    'readMoreUrl' => $readMoreUrl,
                    'readMoreTitle' => $readMoreTitle,
                ]
            ))->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return view('article', [
            'article' => $article,
            'title'   => $title,
            'breadcrumbs'   => $breadcrumbs,
            'readMoreUrl'   => $readMoreUrl,
            'readMoreTitle' => $readMoreTitle,
        ]);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function readMoreData(?Article $article): array
    {
        if (! $article) {
            return [null, null];
        }

        $url = $this->articleUrlBuilder->urlForLocale($article, app()->getLocale());

        if ($url === null) {
            return [null, null];
        }

        return [
            $url,
            $article->translate(app()->getLocale())?->title ?? $article->title,
        ];
    }



    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function showOpinion(Request $request)
    {
        return $this->getArticleForShow([
            'type' => ArticleType::OPINION,
            'id'   => (int)$request->route('id'),
            'slug' => $request->route('slug'),
            'locale' => $request->route('locale'),
        ]);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function showCommon(Request $request)
    {
        return $this->getArticleForShow([
            'type'        => $request->route('type'),
            'category'    => $request->route('category'),
            'subcategory' => $request->route('subcategory'),
            'id'          => (int)$request->route('id'),
            'slug'        => $request->route('slug'),
            'locale'      => $request->route('locale'),
        ]);
    }

    /**
     *  for press_rls  infographics
     *
     * @param Request $request
     * @param ArticleRepository $articleRepository
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View
     */
    public function showByCategoryList(
        Request $request,
        ArticleRepository $articleRepository,
    ): \Illuminate\Http\Response|\Illuminate\Contracts\View\View
    {
        $locale = $request->route('locale');
        $categorySlug = $request->route('typecat');

        abort_unless(in_array($categorySlug, ArticleType::TYPES_CAT, true), 404);

        $category = Category::allCached()->firstWhere('slug', $categorySlug);

        abort_unless($category !== null, 404);

        /** @var LengthAwarePaginator $articles */
        $articles = $articleRepository->getPublishedByCategory(
            $category->id,
            $locale,
            config('article.article.per_page')
        );

        setLastMod(
            $articles->getCollection()->max('updated_at')
        );

        Seo::set(CategorySeo::make($category))->paginate($articles);

        return view('list', [
            'title'    => $category->name,
            'articles' => $articles,
            'paginate' => true,
            'breadcrumbs' => $this->breadcrumbsService->forList($category->name ?? $category->slug),
        ]);
    }


    public function showByTypeList(
        Request $request,
        ArticleRepository $articleRepository,
    ): \Illuminate\Http\Response|\Illuminate\Contracts\View\View
    {
        $locale = $request->route('locale');
        $typeCode = $request->route('type');
        $typeCode = ArticleType::codeFromRoute($typeCode);

        if (in_array($typeCode, ArticleType::TYPES_CAT, true)) {
            return $this->showCategoryList($typeCode, $locale, $articleRepository);
        }

        $type = ArticleType::allCached()
            ->firstWhere('code', $typeCode);

        abort_unless($type !== null, 404);

        $typeId = $type->id;

        /** @var LengthAwarePaginator $articles */
        $articles = $articleRepository->getPublishedByArticleType(
            $typeId,
            $locale,
            config('article.article.per_page')
        );

        setLastMod(
            $articles->getCollection()->max('updated_at')
        );

        Seo::set(ArticleTypeSeo::make($type))->paginate($articles);

        return view('list', [
            'title' => $type->name,
            'articles' => $articles,
            'paginate' => true,
            'breadcrumbs' => $this->breadcrumbsService->forList($type->name ?? $type->code),
        ]);
    }


    private function showCategoryList(
        string $categorySlug,
        string $locale,
        ArticleRepository $articleRepository,
    ) {
        $category = Category::query()
            ->where('slug', $categorySlug)
            ->with('translations')
            ->first();

        abort_unless($category !== null, 404);

        $articles = $articleRepository->getPublishedByCategory(
            $category->id,
            $locale,
            config('article.article.per_page')
        );

        setLastMod(
            $articles->getCollection()->max('updated_at')
        );

        return view('list', [
            'title' => $category->name ?? $category->slug,
            'articles' => $articles,
            'paginate' => true,
        ]);
    }
}
