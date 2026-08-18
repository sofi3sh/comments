<?php

namespace App\View\Components\Containers;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;

class ArticlesWithActionsContainer extends Component
{
    public $articles;

    public string $listUrl;

    /**
     * Create a new component instance.
     */
    public function __construct(?string $locale = null, string $type = ArticleType::ARTICLE)
    {
        $locale ??= app()->getLocale();

        $this->articles = Article::forMainContainer(10, $locale)->get();

        setLastMod(
            $this->articles->max(
                fn (Article $article): ?int => $article->updated_at?->getTimestamp()
            )
        );

        $this->listUrl  = route('locale.type.show.list', [
            'locale' => $locale,
            'type' => ArticleType::codeForRoute($type),
        ]);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.articles-with-actions-container');
    }
}
