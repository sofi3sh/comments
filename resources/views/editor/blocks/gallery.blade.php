<section class="article-image-gallery photo-wrapper" data-article-image-gallery>
    <div class="article-image-gallery__slider swiper swiper-wrappers-group">
        <div class="swiper-wrapper">
            @foreach($images as $image)
                <div class="swiper-slide">
                    <a href="{{ $image['fullSrc'] }}" target="_blank" rel="noopener" class="article-image-gallery__image-button" aria-label="{{ __('editor.gallery.open_full_image') }}">
                        <img
                            src="{{ $image['src'] }}"
                            @if($image['srcset']) srcset="{{ $image['srcset'] }}" sizes="(max-width: 768px) 100vw, 856px" @endif
                            alt="{{ $image['alt'] }}"
                            @if($image['title']) title="{{ $image['title'] }}" @endif
                            loading="lazy"
                            decoding="async"
                        >
                    </a>
                </div>
            @endforeach
        </div>
        @if($images->count() > 1)
            <button type="button" class="article-image-gallery__prev" aria-label="{{ __('editor.gallery.previous_image') }}"></button>
            <button type="button" class="article-image-gallery__next" aria-label="{{ __('editor.gallery.next_image') }}"></button>
        @endif
    </div>
    @if($images->count() > 1)
        <div class="article-image-gallery__pagination" aria-label="{{ __('editor.gallery.navigation') }}"></div>
    @endif
</section>
