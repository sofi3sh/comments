<div class="article-container">

    @if($withLoadPoint)
        <span
            class="load-point"
            data-id="{{ $article->id }}"
            data-title="{{ $articleTitle ?? $title }}"
            data-url="{{ $articleUrl ?? $article->getArticleUrl() }}"
        ></span>
    @endif

    <h1 class="category-title__title">{{ $title }}</h1>

    <div class="article-container__main">
        <div class="article-container__main-left">
            <span class="article-container__date">
                @if($date)
                    {{ $date }}
                @endif
            </span>
            <div class="article-container__views">
                <svg width="19" height="12" viewBox="0 0 19 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.46429 0C5.84778 0 2.56814 1.97862 0.148106 5.19243C-0.0493688 5.45573 -0.0493688 5.82358 0.148106 6.08688C2.56814 9.30456 5.84778 11.2832 9.46429 11.2832C13.0808 11.2832 16.3604 9.30456 18.7805 6.09075C18.9779 5.82745 18.9779 5.45961 18.7805 5.19631C16.3604 1.97862 13.0808 0 9.46429 0ZM9.72371 9.61433C7.32304 9.76534 5.34054 7.78671 5.49155 5.38216C5.61546 3.39967 7.22236 1.79276 9.20486 1.66886C11.6055 1.51785 13.588 3.49647 13.437 5.90102C13.3092 7.87964 11.7023 9.48655 9.72371 9.61433ZM9.60368 7.77897C8.31041 7.86028 7.24172 6.79547 7.32691 5.5022C7.39273 4.43351 8.26007 3.57004 9.32876 3.50034C10.622 3.41903 11.6907 4.48385 11.6055 5.77712C11.5358 6.84968 10.6685 7.71314 9.60368 7.77897Z"/>
                </svg>
                <span
                    @unless($withLoadPoint) id="article-views" @endunless
                    data-article-views-counter
                    data-get-url="{{ route('api.get.article.views', ['article' => $article->id]) }}"
                    data-set-url="{{ route('api.set.article.view', ['locale' => app()->getLocale(), 'article' => $article->id]) }}"
                    data-register-delay="{{ config('views.register_delay') }}"
                    data-csrf-token="{{ csrf_token() }}"
                    hidden
                ></span>
            </div>
        </div>
        <div class="article-container__main-right">
            @if($author)
                @if($authorUrl)
                    <a href="{{ $authorUrl }}" class="article-container__author">
                        {{ $author->localizedName }}
                    </a>
                @else
                    <span class="article-container__author">
                        {{ $author->localizedName }}
                    </span>
                @endif
            @endif

            <div class="article-container__languages">
                @if($languages)
                    @foreach($languages as $language)
                        <a href="{{ $language['url'] }}" class="article-container__language">{{ $language['name'] }}</a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{--  TODO  anchors   --}}
    @if($content)

        @if($videoEmbedUrl)
            <div class="article-video-player" data-video-player data-video-url="{{ $videoEmbedUrl }}">
                <button class="article-video-player__button" type="button" aria-label="Воспроизвести видео">
                    <img
                        class="article-video-player__cover"
                        src="{{ $coverUrl }}"
                        @if($videoThumbnailFallbackUrl) data-youtube-thumbnail data-thumbnail-fallback="{{ $videoThumbnailFallbackUrl }}" @endif
                        alt=""
                        decoding="async"
                        fetchpriority="high"
                    >
                    <span class="article-video-player__icon" aria-hidden="true"></span>
                </button>
            </div>
        @else
            <div class="">
                <img
                    style="width: 100%;"
                    src="{{ $article->getCoverUrl('cover') }}"
                    @if($coverSrcset)
                        srcset="{{ $coverSrcset }}"
                        sizes="(max-width: 768px) 100vw, 1280px"
                    @endif
                    alt=""
                    decoding="async"
                    fetchpriority="high"
                >
            </div>
        @endif

        <!-- <div class="article-container__anchors">
            @foreach($anchors as $anchor )
                <a href="#{{ $anchor['slug'] }}" class="article-container__anchor">{{ $loop->iteration }}. {{ $anchor['text'] }}</a>
            @endforeach
        </div> -->
        <div
            @unless($withLoadPoint) id="article-content" @endunless
            class="article-container__content"
            @if($hasRestContent)
                data-article-rest="1"
                data-rest-url="{{ $restUrl }}"
            @endif
        >
            {!! $content !!}
        </div>

        @if($hasRestContent)
            <div class="article-read-more" data-article-private-placeholder>
                <button
                    type="button"
                    class="article-read-more__link"
                    aria-disabled="true"
                >
                    {{ __('article.buttons.read_full') }}
                </button>
            </div>
        @endif

        @include('articles.partials.article-footer-meta', ['article' => $article])

        @include('articles.partials.read-more', [
            'url' => $readMoreUrl ?? null,
            'title' => $readMoreTitle ?? null,
        ])
    @endif
</div>
