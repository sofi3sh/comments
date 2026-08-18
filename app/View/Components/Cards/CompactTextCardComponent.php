<?php

namespace App\View\Components\Cards;

use App\Helpers\DateHelper;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CompactTextCardComponent extends Component
{
    public $article;

    public $articleUrl;

    public $title;

    public $date;

    /**
     * Create a new component instance.
     */
    public function __construct($article)
    {
        $this->article = $article;
        $this->title = null;
        $this->date = null;

        if ($this->article) {

            $this->articleUrl = $this->article->getArticleUrl();

            try {
                // The Translatable package automatically uses the current locale
                $this->title = $this->article->title_with_markers ?? $this->article->title ?? null;

                $articleDate = $this->article->published_at ?? $this->article->created_at;

                if ($articleDate) {
                    $this->date = $this->formatDate($articleDate);
                }
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Format date based on whether it's today, yesterday, or another date.
     */
    private function formatDate(DateTimeInterface $date): string
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::instance($date);
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $dateStart = $carbonDate->copy()->startOfDay();

        if ($dateStart->equalTo($today)) {
            return __('page.articles-with-actions.today').', '.DateHelper::format($carbonDate, DateHelper::DATE_TIME);
        } elseif ($dateStart->equalTo($yesterday)) {
            return __('page.articles-with-actions.yesterday').', '.DateHelper::format($carbonDate, DateHelper::DATE_TIME);
        } else {
            return DateHelper::format($carbonDate, DateHelper::DATE_DATETIME);
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.compact-text-card-component');
    }
}
