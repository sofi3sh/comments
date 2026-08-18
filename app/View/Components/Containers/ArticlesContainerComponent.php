<?php

namespace App\View\Components\Containers;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
class ArticlesContainerComponent extends Component
{   
    public bool $hasData;

    public LengthAwarePaginator|Collection $leftArticles;

    public LengthAwarePaginator|Collection $rightArticles;

    public function __construct(public array $page)
    {
        $this->leftArticles = $page['leftArticles'];

        $this->rightArticles = $page['rightArticles'];

        $this->hasData =
            $this->leftArticles->count() > 0
            && $this->rightArticles->count() > 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.articles-container-component');
    }
}
