<?php

namespace App\View\Components\Containers;

use App\Models\Articles\Article;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class LiveContainerComponent extends Component
{
    public bool $hasData;
    public LengthAwarePaginator|Collection $articles;
    public ?Article $firstArticle;
    public $restArticles;

    public function __construct(public array $page)
    {
        $this->articles = $page['articles'];
        $this->hasData = $this->articles->count() > 0;
        $this->firstArticle = $this->articles->first();
        $this->restArticles = $this->articles->skip(1);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.live-container-component');
    }
}
