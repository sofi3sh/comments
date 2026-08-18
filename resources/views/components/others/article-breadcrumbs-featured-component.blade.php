<div class="article-breadcrumbs">
    @if($categoryTitle)
        <a class="article-breadcrumbs__main {{ $isVisible ? 'article-breadcrumbs__main--visible' : 'article-breadcrumbs__main--hidden' }}" href="{{ $categoryUrl ?? '#' }}">{{ $categoryTitle }}</a>
        @if($date)
            <span class="article-breadcrumbs__separator {{ $isVisible ? 'article-breadcrumbs__separator--visible' : 'article-breadcrumbs__separator--hidden' }}">•</span>
        @endif
    @endif

    @if($date)
        <span class="article-breadcrumbs__time {{ $isVisible ? 'article-breadcrumbs__time--visible' : 'article-breadcrumbs__time--hidden' }}">{{ $date }}</span>
    @endif
</div>