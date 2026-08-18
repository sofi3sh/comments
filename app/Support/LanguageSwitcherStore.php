<?php

namespace App\Support;

use App\Contracts\LocalizedUrlContract;

/**
 * Per-request holder for the model whose locale URLs the language switcher
 * should point at.
 *
 * The switcher is rendered from the shared header partial, which has no access
 * to the controller's model — hence a holder rather than a view variable.
 *
 * Bound as scoped() alongside SeoManager/SchemaGraph/LastModifiedStore so the
 * value is dropped between requests under a worker runtime. This replaces an
 * earlier View::share('languageSwitcher', ...), which wrote into the view
 * factory's shared-data array; that array is not per-request state and would
 * have leaked one page's switcher onto the next request's pages.
 */
final class LanguageSwitcherStore
{
    private ?LocalizedUrlContract $model = null;

    public function set(?LocalizedUrlContract $model): void
    {
        $this->model = $model;
    }

    public function model(): ?LocalizedUrlContract
    {
        return $this->model;
    }
}
