<div class="vertical-card">
    @if($thumbnail)
    <a href="{{ $articleUrl }}" class="vertical-card__image @if($isVideo) card-video-cover @endif">
            <img
                src="{{ $thumbnail }}"
                @if($thumbnailSrcset) srcset="{{ $thumbnailSrcset }}" @endif
                @if($thumbnailSrcset) sizes="{{ $thumbnailSizes }}" @endif
                alt="Vertical Card"
                loading="lazy"
                decoding="async"
            />
            @if($isVideo)
                <span class="card-video-cover__play" aria-hidden="true"></span>
            @endif
    </a>
    @else
        <div class="vertical-card__image"></div>
    @endif

    @if($title)
    <a class="vertical-card__title" href="{{ $articleUrl }}">
        {{ $title }}
    </a>
    @else
        <div class="vertical-card__title"></div>
    @endif
    
    @if($excerpt)
    <a href="{{ $articleUrl }}" class="vertical-card__description">
        {{ $excerpt }}
    </a>
    @else
        <div class="vertical-card__description"></div>
    @endif

    @if($article)
    <x-others.article-breadcrumbs-component :article="$article" />
    @endif

</div>
