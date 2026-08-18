<div class="compact-card">
    @if($thumbnail)
    <a href="{{ $articleUrl }}" class="compact-card__image @if($isVideo) card-video-cover @endif">
            <img
                src="{{ $thumbnail }}"
                @if($thumbnailSrcset) srcset="{{ $thumbnailSrcset }}" @endif
                @if($thumbnailSrcset) sizes="{{ $thumbnailSizes }}" @endif
                alt="Compact Card"
                loading="lazy"
                decoding="async"
            >
            @if($isVideo)
                <span class="card-video-cover__play" aria-hidden="true"></span>
            @endif
    </a>
    @else
        <div class="compact-card__image"></div>
    @endif

    @if($title)
    <a href="{{ $articleUrl }}" class="compact-card__title">{{ $title }}</a>
    @else
        <div class="compact-card__title"></div>
    @endif

    @if($article)
    <x-others.article-breadcrumbs-component :article="$article" />
    @endif
</div>
