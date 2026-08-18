<?php

namespace App\View\Components\Containers;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;
class RecomendedFeaturedContainerComponent extends Component
{
    public LengthAwarePaginator|Collection|null $articles;
    public $title;
    public $option;
    public string|null $showAllLink;
    /**
     * Create a new component instance.
     */
    public function __construct(
        $option='featured',
        LengthAwarePaginator|Collection|null $articles = null,
        $code=null
    )
    {
        $this->option = $option;
        $this->title = $option === 'featured' ? __('page.featured.title') : __('page.recommended.title');
        $this->articles = $articles;
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
        return view('components.containers.recomended-featured-container-component');
    }
}
