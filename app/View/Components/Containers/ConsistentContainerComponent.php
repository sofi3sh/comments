<?php

namespace App\View\Components\Containers;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsistentContainerComponent extends Component
{
    public LengthAwarePaginator|Collection|null $articles;
    public string|null $showAllLink;
    public bool $paginate;

    /**
     * Create a new component instance.
     */
    public function __construct(
        LengthAwarePaginator|Collection|null $articles = null,
        bool $paginate = false,
        $code=null
    )
    {
        $this->articles = $articles;
        $this->paginate = $paginate && $articles instanceof LengthAwarePaginator && $articles->hasPages();
        $this->showAllLink = $code
            ? route('locale.collection.show', [
                'locale' => app()->getLocale(),
                'code' => $code
            ])
            : null;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.consistent-container-component');
    }
}
