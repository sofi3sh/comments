<?php

namespace App\View\Components\Containers;

use App\Helpers\DateHelper;
use App\Models\Articles\Article;
use App\Models\Settings\Locale;
use App\Services\Article\ArticleAnchorProcessor;
use App\Services\Article\ArticleContentService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ArticleContainerComponent extends Component
{
    public ?string $content;

    public string $date;

    public int $views;

    public $author;

    public ?string $authorUrl = null;

    public array $anchors = [];

    public array $languages = [];

    public string $coverUrl;

    public ?string $restUrl = null;

    public bool $hasRestContent = false;

    public string $coverSrcset;

    public string $title;


    /**
     * Create a new component instance.
     */
    public function __construct(
        public Article $article,
        ArticleContentService $contentService,
        ArticleAnchorProcessor $anchorProcessor,
        public bool $withLoadPoint = false,
        public ?string $articleTitle = null,
        public ?string $articleUrl = null,
        public ?string $readMoreUrl = null,
        public ?string $readMoreTitle = null,
        public ?string $videoEmbedUrl = null,
        public ?string $videoThumbnailUrl = null,
        public ?string $videoThumbnailFallbackUrl = null,
    )
    {
        setLastMod($article->updated_at);
        $locale = app()->getLocale();
        $this->title = $article->title_with_markers ?? $article->title ?? '';

        $this->date = DateHelper::localeFormat(
            $article->created_at,
            DateHelper::DATE_LOCALE_DATETIME,
            $locale
        );

        $this->views     = $article->views;
        $this->author    = $article->authors?->first();
        $this->authorUrl = $this->getAuthorUrl($locale);
        $this->languages = $this->getLanguages($locale);
        $this->coverSrcset = $this->videoEmbedUrl ? '' : $article->getCoverSrcset();

        $parts = $contentService->splitContent($this->article);
        $content     = $parts->first;
        $restContent = $parts->rest;

        if ($content === null || trim($content) === '') {
            abort(404);
        }

        $processed = $anchorProcessor->process($content); //todo

        $this->content = $processed['content'];
        $this->anchors = $processed['anchors'];

        $this->coverUrl = $this->videoThumbnailUrl ?: $this->article->getCoverUrl('cover');
        $this->hasRestContent = $restContent !== null && trim($restContent) !== '';
        $this->restUrl = $this->hasRestContent ? $contentService->getRestUrl($locale, $this->article->id) : null;
    }


    protected function getAuthorUrl(string $locale): ?string
    {
        if ($this->author === null || $this->author->trashed()) {
            return null;
        }

        return route('locale.author', [
            'locale' => $locale,
            'slug'   => $this->author->slug,
            'id'     => $this->author->id,
        ]);
    }

    /**
     * Get all available languages for current article
     * except the current one.
     */
    protected function getLanguages(string $currentLocale): array
    {
        $activeLocales = Locale::getAvailableAsArr('code');

        $articleLocales = $this->article->translations
            ->pluck('locale')
            ->toArray();

        return collect($articleLocales)

            ->filter(function ($locale) use ($activeLocales, $currentLocale) {
                return in_array($locale, $activeLocales) && $locale !== $currentLocale;
            })
            ->map(function ($locale) {
                return [
                    'name' => strtoupper($locale),
                    'url'  => $this->getLocalizedUrl($locale),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Generate localized URL for the article using the slug for the target locale.
     */
    protected function getLocalizedUrl(string $locale): string
    {
        return $this->article->getArticleUrlForLocale($locale);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.containers.article-container-component');
    }
}
