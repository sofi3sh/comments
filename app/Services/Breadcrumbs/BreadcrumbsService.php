<?php

namespace App\Services\Breadcrumbs;

use App\Helpers\LocaleUrlHelper;
use App\Models\Articles\Article;
use App\Models\Articles\Category;
use Illuminate\Support\Str;

class BreadcrumbsService
{
    public function forArticle(Article $article): array
    {
        if ($article->type?->code === 'page') {
            return [
                [
                    'label' => __('page.breadcrumbs.home'),
                    'url' => LocaleUrlHelper::localizedHomepageUrl(),
                ],
                [
                    'label' => $article->title,
                    'url' => null,
                ],
            ];
        }

        $categoryLabel = $article->category?->name;
        $categoryLabel = $categoryLabel !== null && $categoryLabel !== '' ? $categoryLabel : ($article->type?->name ?? __('page.dossier.title'));
        $categoryLabel = Str::ucfirst($categoryLabel);

        $categoryUrl = $article->category
            ? $this->categoryUrl($article->category)
            : null;

        return [
            [
                'label' => __('page.breadcrumbs.home'),
                'url' => LocaleUrlHelper::localizedHomepageUrl(),
            ],
            [
                'label' => $categoryLabel,
                'url' => $categoryUrl,
            ],
            [
                'label' => $article->title,
                'url' => null,
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public function forCategory(Category $category): array
    {
        return [
            [
                'label' => __('page.breadcrumbs.home'),
                'url' => LocaleUrlHelper::localizedHomepageUrl(),
            ],
            [
                'label' => Str::ucfirst($category->name ?? $category->slug),
                'url' => $this->categoryUrl($category),
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public function forList(string $title): array
    {
        return [
            [
                'label' => __('page.breadcrumbs.home'),
                'url' => LocaleUrlHelper::localizedHomepageUrl(),
            ],
            [
                'label' => Str::ucfirst($title),
                'url' => null,
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public function forDossier(): array
    {
        return [
            [
                'label' => __('page.breadcrumbs.home'),
                'url' => LocaleUrlHelper::localizedHomepageUrl(),
            ],
            [
                'label' => __('page.dossier.title'),
                'url' => null,
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public function forSignificant(string $type): array
    {
        $typeLabel = __('page.significant.' . $type);

        return [
            [
                'label' => __('page.breadcrumbs.home'),
                'url' => LocaleUrlHelper::localizedHomepageUrl(),
            ],
            [
                'label' => __('page.dossier.title'),
                'url' => $this->localizedRoute('dossier'),
            ],
            [
                'label' => Str::ucfirst($typeLabel),
                'url' => null,
            ],
        ];
    }

    public function toSchemaItems(array $breadcrumbs, string $currentUrl): array
    {
        $items = [];

        foreach ($breadcrumbs as $index => $crumb) {
            $isLast = $index === count($breadcrumbs) - 1;

            $items[] = [
                'name' => $crumb['label'],
                'url' => $isLast ? $currentUrl : ($crumb['url'] ?? $currentUrl),
            ];
        }

        return $items;
    }

    private function localizedRoute(string $routeName, array $params = []): string
    {
        $locale = app()->getLocale();

        return route('locale.' . $routeName, array_merge(['locale' => $locale], $params));
    }

    private function categoryUrl(Category $category): ?string
    {
        $homepageCategory = $category->parent_id
            ? $category->parent
            : $category;

        $site = $homepageCategory?->getSite();

        if (filled($site?->domain)) {
            return route('category.homepage', [
                'locale' => app()->getLocale(),
                'domain' => $site->domain,
            ]);
        }

        return null;
    }
}
