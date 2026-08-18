<div class="detail-video-card">
    @if($thumbnail)
        <div class="detail-video-card__video">
            <a href="{{ $articleUrl }}" class="detail-video-card__layout">
                <img
                    class="detail-video-card__image"
                    src="{{ $thumbnail }}"
                    @if($thumbnailFallbackUrl) data-youtube-thumbnail data-thumbnail-fallback="{{ $thumbnailFallbackUrl }}" @endif
                    @if($thumbnailSrcset) srcset="{{ $thumbnailSrcset }}" @endif
                    @if($thumbnailSrcset) sizes="{{ $thumbnailSizes }}" @endif
                    alt="Detail Video Card"
                    decoding="async"
                >
                <div class="detail-video-card__icon">
                    <svg viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M67.2716 14.598C64.7448 11.5944 60.0796 10.3691 51.1702 10.3691H18.8291C9.71577 10.3691 4.97158 11.6734 2.45429 14.8713C0 17.9892 0 22.5832 0 28.9415V41.0603C0 53.3783 2.912 59.6324 18.8291 59.6324H51.1704C58.8966 59.6324 63.1779 58.5513 65.9477 55.9005C68.7881 53.1823 70 48.744 70 41.0603V28.9415C70 22.2362 69.8101 17.615 67.2716 14.598ZM44.9402 36.674L30.2542 44.3494C29.9101 44.5295 29.5253 44.6177 29.1371 44.6056C28.7488 44.5934 28.3703 44.4813 28.0381 44.2801C27.7059 44.0789 27.4312 43.7953 27.2406 43.4569C27.05 43.1184 26.95 42.7365 26.9502 42.3481V27.0465C26.9502 26.6587 27.0502 26.2774 27.2403 25.9394C27.4305 25.6014 27.7045 25.3181 28.036 25.1167C28.3675 24.9154 28.7452 24.8028 29.1328 24.7898C29.5204 24.7769 29.9049 24.864 30.249 25.0427L44.935 32.6687C45.3016 32.859 45.6089 33.1462 45.8236 33.499C46.0382 33.8518 46.152 34.2568 46.1526 34.6698C46.1531 35.0828 46.0404 35.488 45.8266 35.8414C45.6128 36.1948 45.3063 36.4827 44.9402 36.674Z"/>
                    </svg>
                </div>
            </a>
        </div>
    @else
        <div class="detail-video-card__video"></div>
    @endif
   
    <div class="detail-video-card__content">
        @if($title)
            <a href="{{ $articleUrl }}" class="detail-video-card__title">
                {{ $title }}
            </a>
        @else
            <div class="detail-video-card__title"></div>
        @endif

        @if($excerpt || $author)
            <a href="{{ $articleUrl }}" class="detail-video-card__description">
                @if($author)
                    <span class="detail-video-card__description-author">{{ $author->name ?? '' }}:</span>
                @endif
                @if($excerpt)
                    {{ $excerpt }}
                @endif
            </a>
        @else
            <div class="detail-video-card__description"></div>
        @endif

    </div>
</div>
