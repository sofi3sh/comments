<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Category;
use Illuminate\Http\Request;


class ArticleTypeController extends Controller
{

    public function dropdown(Request $request)
    {
        $locale = $request->route('locale');
        $currentType = ArticleType::codeFromRoute($request->query('current_type', ArticleType::NEWS));

        if ($locale !== '') {
            app()->setLocale($locale);
        }

        $types = ArticleType::homepageDropdownCached($locale);
        $categories = Category::typeDropdownCategoriesCached($locale);

        $typeItems = $types->map(fn (ArticleType $type) => [
            'code' => $type->code,
            'name' => $type->translate($locale)?->name ?? $type->code,
            'url' => $this->mainSiteUrl($locale . '/' . ArticleType::codeForRoute($type->code)),
            'active' => $type->code === $currentType,
        ]);

        $categoryItems = $categories->map(fn (Category $category) => [
            'code' => $category->slug,
            'name' => $category->translate($locale)?->name ?? $category->slug,
            'url'  => $this->mainSiteUrl($locale . '/' . $category->slug),
            'active' => $category->slug === $currentType,
        ]);

        $dossierItem = collect([[
            'code' => 'dossier',
            'name' => __('page.dossier.title'),
            'url'  => $this->mainSiteUrl($locale . '/dossier'),
            'active' => $currentType === 'dossier',
        ]]);

        $items = $typeItems->concat($categoryItems)->concat($dossierItem)->values();
        $currentItem = $items->firstWhere('active')
            ?? $items->firstWhere('code', ArticleType::NEWS)
            ?? $items->first();

        return view('partials.article-types-dropdown', [
            'items' => $items,
            'currentTypeName' => $currentItem['name'] ?? __('News'),
        ]);
    }

    private function mainSiteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
