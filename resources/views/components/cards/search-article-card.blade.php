<div class="horizontal-card">
    @if($thumbnail)
        <a href="{{ $url }}" class="horizontal-card__image">
            <img
                src="{{ $thumbnail }}"
                @if(!empty($thumbnailSrcset)) srcset="{{ $thumbnailSrcset }}" @endif
                @if(!empty($thumbnailSrcset)) sizes="{{ $thumbnailSizes }}" @endif
                alt="Horizontal Card"
                loading="lazy"
                decoding="async"
            />
        </a>
    @else
        <div class="horizontal-card__image"></div>
    @endif

    <div class="horizontal-card__container">

        <div class="search-card__meta">
            @if($publishedAt || $categoryTitle || $viewsCount)
                <div class="search-card__meta-inner">

                    @if($publishedAt)
                        <span class="search-card__meta-item">
                            {{ $publishedAt }}
                        </span>
                    @endif

                    @if($categoryTitle)
                        <span class="search-card__meta-separator"></span>

                        <span class="search-card__meta-item">
                            {{ $categoryTitle }}
                        </span>
                    @endif

                    @if($viewsCount)
                        <span class="search-card__meta-separator"></span>
                        @include('components.others.article-views-count', ['viewsCount' => $viewsCount])
                    @endif

                </div>
            @endif
        </div>

        <div class="horizontal-card__container-header">
            @if($title)
                <a class="horizontal-card__title" href="{{ $url }}">
                    {{ $title }}
                </a>
            @else
                <div class="horizontal-card__title"></div>
            @endif
        </div>

        @if($excerpt)
            <a href="{{ $url }}" class="horizontal-card__container-body">
                {{ $excerpt }}
            </a>
        @else
            <div class="horizontal-card__container-body"></div>
        @endif

    </div>

</div>
