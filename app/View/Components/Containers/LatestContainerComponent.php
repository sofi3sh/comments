<?php

namespace App\View\Components\Containers;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
class LatestContainerComponent extends Component
{
    public bool $hasData;

    public LengthAwarePaginator|Collection $articles;

    public function __construct(public array $page)
    {
        $this->articles = $page['articles'] ?? [];

        $this->hasData = $this->articles->count() > 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.latest-container-component');
    }
}
