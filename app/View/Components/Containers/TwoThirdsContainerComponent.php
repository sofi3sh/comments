<?php

namespace App\View\Components\Containers;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Articles\Article;
class TwoThirdsContainerComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public string $type = 'dossier', public ?Article $article = null, public ?LengthAwarePaginator $articles = null, public ?string $letter = null, public ?string $readMoreUrl = null, public ?string $readMoreTitle = null)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.two-thirds-container-component');
    }
}
