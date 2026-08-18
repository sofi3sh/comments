<?php

namespace App\View\Components\Others;

use App\Repositories\TagRepository;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TagComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        private readonly TagRepository $tagRepository
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        $tags = $this->tagRepository->getHomepageTags(
            app()->getLocale(),
            config('tags.homepage.limit', 5)
        );

        return view('components.others.tag-component', [
            'tags' => $tags,
        ]);
    }
}