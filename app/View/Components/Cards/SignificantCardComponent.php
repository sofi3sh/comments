<?php

namespace App\View\Components\Cards;

use App\Helpers\DateHelper;
use App\Models\Articles\Article;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SignificantCardComponent extends Component
{
    public $name;

    public $articleUrl;

    public ?string $birthdate = null;

    public $thumbnail;

    public string $thumbnailSrcset = '';

    public string $thumbnailSizes = '(max-width: 768px) 100vw, 640px';

    /**
     * Create a new component instance.
     */
    public function __construct(public Article $article)
    {
        setLastMod($this->article->updated_at);
        if ($this->article !== null) {
            // The Translatable package automatically uses the current locale
            $this->name = $this->article->title_with_markers ?? $this->article->title ?? null;
            $this->birthdate = $this->article->published_at
                ? DateHelper::format($this->article->published_at)
                : null;
            $this->thumbnail = $this->article->getCoverUrl('card_lg');
            $this->thumbnailSrcset = $this->article->getCoverSrcset(['card_sm', 'card_lg']);
            $this->articleUrl = $this->article->getArticleUrl();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.significant-card-component');
    }
}
