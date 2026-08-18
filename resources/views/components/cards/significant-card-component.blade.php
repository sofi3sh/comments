<a href="{{ $articleUrl }}" class="significant-list__item">
    @if($article->id)
    <div class="significant-list__item-number">{{ $article->id }}</div>
    @endif
    <div class="significant-list__item-photo">
        <img
            src="{{ $thumbnail }}"
            @if($thumbnailSrcset) srcset="{{ $thumbnailSrcset }}" @endif
            @if($thumbnailSrcset) sizes="{{ $thumbnailSizes }}" @endif
            alt="Person photo"
            class="significant-list__item-photo-img"
            loading="lazy"
            decoding="async"
        >
    </div>
    <div class="significant-list__item-info">
        @if($name)
        <div class="significant-list__item-name">{{ $name }}</div>
        @endif
        @if($birthdate)
        <div class="significant-list__item-birthdate">{{ $birthdate }}</div>
        @endif
    </div>
</a>
