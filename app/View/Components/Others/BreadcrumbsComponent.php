<?php

namespace App\View\Components\Others;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BreadcrumbsComponent extends Component
{
    public function __construct(public array $items = [])
    {
    }

    public function render(): View|Closure|string
    {
        return view('components.others.breadcrumbs-component');
    }
}

