<?php

namespace App\View\Components\Others;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PaginationComponent extends Component
{
    public LengthAwarePaginator $paginator;
    public bool $hasPages;
    public bool $onFirstPage;
    public bool $hasMorePages;
    public int $currentPage;
    public int $lastPage;
    public int $startPage;
    public int $endPage;
    public array $pageRange;

    /**
     * Create a new component instance.
     */
    public function __construct(LengthAwarePaginator $paginator)
    {
        $this->paginator = $paginator;
        $this->hasPages = $paginator->hasPages();
        $this->onFirstPage = $paginator->onFirstPage();
        $this->hasMorePages = $paginator->hasMorePages();
        $this->currentPage = $paginator->currentPage();
        $this->lastPage = $paginator->lastPage();
        
        $this->calculatePageRange();
    }

    /**
     * Calculate the page range to display.
     */
    protected function calculatePageRange(): void
    {
        $sidePages = 2;

        $this->startPage = max(
            1,
            $this->currentPage - $sidePages
        );

        $this->endPage = min(
            $this->lastPage,
            $this->currentPage + $sidePages
        );

        $this->pageRange = [];

        foreach (
            $this->paginator->getUrlRange(
                $this->startPage,
                $this->endPage
            ) as $page => $url
        ) {
            $this->pageRange[] = [
                'page' => $page,
                'url' => $url,
                'isActive' => $page === $this->currentPage,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.others.pagination-component');
    }
}
