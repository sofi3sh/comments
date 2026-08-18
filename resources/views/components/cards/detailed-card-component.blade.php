<div class="detailed-card">
    @if($thumbnail)
    <a href="{{ $articleUrl }}" @if($isVideo) class="card-video-cover" @endif>
        <img
            class="detailed-card__image"
            src="{{ $thumbnail }}"
            @if($thumbnailSrcset) srcset="{{ $thumbnailSrcset }}" @endif
            @if($thumbnailSrcset) sizes="{{ $thumbnailSizes }}" @endif
            alt="Detailed Card"
            decoding="async"
        />
        @if($isVideo)
            <span class="card-video-cover__play" aria-hidden="true"></span>
        @endif
    </a>
    @else
        <div class="detailed-card__image"></div>
    @endif

    <div class="detailed-card__content">
        @if($title)
            <strong class="detailed-card__title">{{ $title }}</strong>
        @else
            <div class="detailed-card__title"></div>
        @endif

        @if($excerpt)
            <p class="detailed-card__description">{{ $excerpt }}</p>
        @else
            <p class="detailed-card__description"></p>
        @endif

        @if($author)
            <p class="detailed-card__author">By <span class="detailed-card__author-link">{{ $author->localizedName ?? '' }}</span></p>
        @else
            <p class="detailed-card__author"></p>
        @endif

        @if($article)
            <x-others.article-breadcrumbs-component :article="$article" />
        @endif
    </div>
</div>
