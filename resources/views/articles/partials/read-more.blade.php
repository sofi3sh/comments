@if(!empty($url))
    <div class="article-read-more" data-related-article-trigger>
        <span class="article-read-more__label">{{ $label ?? __('article.buttons.read_more') }}</span>
        <a href="{{ $url }}" class="article-read-more__link">
            {{ $title ?? '' }}
        </a>
    </div>
@endif