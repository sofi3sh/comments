<?php

namespace App\View\Components\Others;

use App\Services\Localization\LanguageSwitcherBuilder;
use App\Support\LanguageSwitcherStore;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcherComponent extends Component
{
    public array $locales;

    public string $currentLocale;

    /**
     * Create a new component instance.
     *
     * Pages that are a single translatable entity (article, tag) put that model
     * in the LanguageSwitcherStore so the switcher links to its per-locale URLs.
     * Everywhere else the store is empty and the builder falls back to
     * translating the current path, so both cases go through build().
     */
    public function __construct(
        LanguageSwitcherBuilder $builder,
        LanguageSwitcherStore $store,
    ) {
        $this->locales = $builder->build($store->model());
        $this->currentLocale = app()->getLocale();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.others.language-switcher-component');
    }
}
