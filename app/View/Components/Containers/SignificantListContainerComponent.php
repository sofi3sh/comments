<?php

namespace App\View\Components\Containers;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Pagination\LengthAwarePaginator;

class SignificantListContainerComponent extends Component
{
    public string $locale;
    public $alphabet;
    public $currentLetter;
    public bool $paginate;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public LengthAwarePaginator $articles,
        public ?string $letter = null,
        public string $type = 'persons'
    )
    {
        $this->locale = app()->getLocale();
        $this->alphabet = $this->getAlphabet($this->locale);
        $this->currentLetter = $letter;
        $this->paginate = $articles->count() > 0;
    }

    /**
     * Get alphabet letters based on locale.
     */
    private function getAlphabet(string $locale): array
    {
        return match ($locale) {
            'ru' => $this->getCyrillicAlphabet(),
            'uk' => $this->getUkrainianAlphabet(),
            'en' => range('A', 'Z'),
            default => range('A', 'Z'),
        };
    }

    /**
     * Get Russian Cyrillic alphabet.
     */
    private function getCyrillicAlphabet(): array
    {
        return ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'];
    }

    /**
     * Get Ukrainian Cyrillic alphabet.
     */
    private function getUkrainianAlphabet(): array
    {
        return ['А', 'Б', 'В', 'Г', 'Ґ', 'Д', 'Е', 'Є', 'Ж', 'З', 'И', 'І', 'Ї', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь', 'Ю', 'Я'];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.significant-list-container-component');
    }
}
