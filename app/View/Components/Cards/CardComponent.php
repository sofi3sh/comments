<?php

namespace App\View\Components\Cards;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Services\Article\YouTubeVideoResolver;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardComponent extends Component
{
    public $article;
    public $articleUrl;
    public $isVisible;
    public $title;
    public $excerpt;
    public $thumbnail;
    public string $thumbnailSrcset = '';
    public string $thumbnailSizes = '';
    public $viewsCount;
    public $author;
    public ?string $youtubeId = null;
    public ?string $thumbnailFallbackUrl = null;
    public bool $isVideo = false;

    /**
     * Create a new component instance.
     */
    public function __construct(public string $option = 'vertical', Article $article = null, $isVisible = true)
    {
        $this->article = $article;
        setLastMod($this->article?->updated_at);
        $this->articleUrl = $this->article->getArticleUrl();
        $this->title = null;
        $this->excerpt = null;
        $this->thumbnail = null;
        $this->viewsCount = 0;
        $this->author = null;
        $this->isVisible = $isVisible;
        $this->isVideo = (int) $this->article->type_id === ArticleType::getTypeId(ArticleType::VIDEO);

        if ($this->article !== null && $this->articleUrl !== '#') {
            try {
                // The Translatable package automatically uses the current locale
                $this->title = $this->article->title_with_markers ?? $this->article->title ?? null;

                if (in_array($this->option, ['vertical', 'horizontal', 'detail-video', 'detailed'])) {
                    $this->excerpt = $this->article->excerpt ? \Str::limit($this->article->excerpt, 400) : null; //todo
                }

                if (in_array($this->option, ['vertical', 'horizontal', 'compact', 'video', 'detail-video', 'detailed'])) {
                    $this->thumbnail = $this->article->getCoverUrl($this->getCoverSize()) ?? null;
                    $this->thumbnailSrcset = $this->article->getCoverSrcset($this->getCoverSrcsetSizes());
                    $this->thumbnailSizes  = $this->getCoverSizesAttribute();
                }

                if (in_array($this->option, ['video', 'detail-video'])) {
                    $this->youtubeId = $this->article->relationLoaded('meta')
                        ? $this->article->meta->first()?->value
                        : $this->article->meta()
                            ->where('field', 'youtube_id')
                            ->whereNull('locale')
                            ->value('value');

                    $youtubeThumbnail = YouTubeVideoResolver::thumbnailUrl($this->youtubeId);
                    if ($youtubeThumbnail) {
                        $this->thumbnail = $youtubeThumbnail;
                        $this->thumbnailFallbackUrl = YouTubeVideoResolver::thumbnailFallbackUrl($this->youtubeId);
                        $this->thumbnailSrcset = '';
                        $this->thumbnailSizes = '';
                    }
                }

                if (in_array($this->option, ['horizontal', 'text'])) {
                    $this->viewsCount = $this->article->views ?? 0;
                }

                if (in_array($this->option, ['detail-video', 'detailed'])) {
                    try {
                        if (!$this->article->relationLoaded('authors')) {
                            $this->article->load('authors');
                        }
                        $this->author = $this->article->authors?->first();
                    } catch (\Exception $e) {
                        $this->author = null;
                    }
                }
            } catch (\Exception $e) {
            }
        }
    }

    private function getCoverSize(): string
    {
        return match ($this->option) {
            'compact'      => 'card_sm',
            'vertical'     => 'card_lg',
            'horizontal'   => 'card_lg',
            'video'        => 'card_lg',
            'detail-video' => 'cover',
            'detailed'     => 'cover',
            default => 'cover',
        };
    }

    private function getCoverSrcsetSizes(): array
    {
        return match ($this->option) {
            'compact'      => ['card_sm', 'card_lg'],
            'vertical'     => ['card_sm', 'card_lg'],
            'horizontal'   => ['card_sm', 'card_lg'],
            'video'        => ['card_sm', 'card_lg'],
            'detail-video' => ['card_lg', 'cover'],
            'detailed'     => ['card_lg', 'cover'],
            default => ['card_sm', 'card_lg', 'cover'],
        };
    }

    private function getCoverSizesAttribute(): string
    {
        return match ($this->option) {
            'compact'      => '(max-width: 640px) 100vw, 320px',
            'vertical'     => '(max-width: 768px) 100vw, 640px',
            'horizontal'   => '(max-width: 768px) 100vw, 640px',
            'video'        => '(max-width: 768px) 100vw, 640px',
            'detail-video' => '(max-width: 768px) 100vw, 1280px',
            'detailed'     => '(max-width: 768px) 100vw, 1280px',
            default => '100vw',
        };
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        switch ($this->option) {
            case 'vertical':
                return view('components.cards.vertical-card-component');
            case 'horizontal':
                return view('components.cards.horizontal-card-component');
            case 'text':
                return view('components.cards.text-card-component');
            case 'compact':
                return view('components.cards.compact-card-component');
            case 'video':
                return view('components.cards.video-card-component');
            case 'detail-video':
                return view('components.cards.detail-video-card-component');
            case 'detailed':
                return view('components.cards.detailed-card-component');
            default:
                return view('components.cards.text-card-component');
        }
    }

    public function shouldRender(): bool
    {
        return $this->article !== null;
    }
}
