<?php

namespace App\Http\Controllers;

use App\Facades\SchemaGraph;
use App\Facades\Seo;
use App\Models\Articles\Article;
use App\Models\Articles\ArticlesBlockSetting;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Category;
use App\Repositories\ArticleRepository;
use App\SEO\Pages\CategorySeo;
use App\SEO\Pages\DossierSeo;
use App\SEO\Pages\DossierTypeSeo;
use App\SEO\Schemas\BreadcrumbSchema;
use App\View\PageBuilders\HomePageBuilder;
use Illuminate\Http\Request;
use App\Services\Breadcrumbs\BreadcrumbsService;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends Controller
{
    public function __construct(private readonly BreadcrumbsService $breadcrumbsService)
    {
    }


    public function dossier()
    {
        $breadcrumbs = $this->breadcrumbsService->forDossier();
        SchemaGraph::add(BreadcrumbSchema::make(
            $this->breadcrumbsService->toSchemaItems($breadcrumbs, url()->current())
        ));

        Seo::set(DossierSeo::make());

        return view('dossier', [
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function significant(Request $request)
    {
        $type = $request->route('type');
        $letter = $request->route('letter');

        $allowedTypes = ['persons', 'company'];
        if (! in_array($type, $allowedTypes, true)) {
            if ($letter === null) {
                $letter = $type;
            }
            $type = 'persons';
        }

        //@todo  refactor
        $codeType = match ($type) {
            'persons' => 'person',
            'company' => 'company'
        };

        $locale = app()->getLocale();
        $typeId = ArticleType::where('code', $codeType)->value('id');
        $articles = Article::forSignificantList($typeId, null, $letter)
            ->paginate(config('article.dossier.per_page', 10));

        setLastMod(
            $articles->getCollection()->max(
                fn (Article $article): ?int => ($article->updated_at ?? $article->created_at)?->getTimestamp()
            )
        );

        $path = route('locale.significant', ['type' => $type,'locale'=> $locale]);

        if ($letter !== null) {
            $path = route('locale.significant', ['locale'=> $locale,'type' => $type, 'letter' => $letter]);
        }

        $articles->setPath($path);

        $breadcrumbs = $this->breadcrumbsService->forSignificant($type);
        SchemaGraph::add(BreadcrumbSchema::make(
            $this->breadcrumbsService->toSchemaItems($breadcrumbs, url()->current())
        ));

        Seo::set(DossierTypeSeo::make($type));

        return view('significant', [
            'type'     => $type,
            'articles' => $articles,
            'letter'   => $letter,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
