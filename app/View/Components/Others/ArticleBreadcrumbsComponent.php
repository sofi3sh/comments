<?php

namespace App\View\Components\Others;

use App\Helpers\DateHelper;
use App\Models\Articles\Article;
use App\Models\Articles\Category;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ArticleBreadcrumbsComponent extends Component
{
    public $article;

    public $category;

    public $categoryTitle;

    public $author;

    public $authorName;

    public $authorUrl;

    public $date;

    public $isVisible;

    public $categoryUrl;

    /**
     * Create a new component instance.
     */
    public function __construct(public string $option = 'classic', ?Article $article = null, bool $isVisible = true)
    {
        // Ініціалізуємо всі змінні значеннями за замовчуванням
        $this->article = $article;
        $this->category = null;
        $this->categoryTitle = null;
        $this->author = null;
        $this->authorName = null;
        $this->authorUrl = null;
        $this->date = null;
        $this->isVisible = $isVisible;
        $this->categoryUrl = null;

        if ($this->article === null) {
            return;
        }

        if (! $this->article->relationLoaded('category')) {
            $this->article->load('category');
        }

        $this->category = $this->article->category?->parent_id
            ? $this->article->category->parent
            : $this->article->category;

        if ($this->category !== null) {
            $this->categoryTitle = $this->category->name ?? null;
            $this->categoryUrl = $this->makeCategoryUrl();
        }

        $publishedAt = $this->article->published_at ?? $this->article->created_at;
        $this->date = $publishedAt !== null
            ? DateHelper::format($publishedAt, DateHelper::DATE_DATETIME_SLASH)
            : null;

        if ($this->option === 'classic') {
            if (! $this->article->relationLoaded('authors')) {
                $this->article->load('authors');
            }

            $this->author     = $this->article->authors?->first();
            $this->authorName = $this->author?->name ?? null;
            $this->authorUrl  = $this->makeAuthorUrl();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        if ($this->option === 'featured') {
            return view('components.others.article-breadcrumbs-featured-component');
        }

        return view('components.others.article-breadcrumbs-component');
    }

    private function makeCategoryUrl(): ?string
    {
        $site = $this->category?->getSite();

        if (! $site) {
            return null;
        }

        return route('category.homepage', [
            'locale' => app()->getLocale(),
            'domain'   => $site->domain,
        ]);
    }

    private function makeAuthorUrl(): ?string
    {
        if ($this->author === null || $this->author->trashed() || empty($this->author->slug)) {
            return null;
        }

        return route('locale.author', [
            'locale' => app()->getLocale(),
            'slug'   => $this->author->slug,
            'id'     => $this->author->id,
        ]);
    }
}
