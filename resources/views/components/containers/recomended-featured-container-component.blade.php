<div class="recommended-featured">
    <div class="recommended-featured-title">
        <strong class="recommended-featured-title--text">{{$title}}</strong>
        
        @if($option === 'recommended' && $showAllLink)
            <a class="recommended-featured-title--link" href="{{$showAllLink}}">
                {{ __('page.recommended.view-all') }}
                <svg width="5" height="8" viewBox="0 0 5 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.04407 4.04477C4.23977 3.84907 4.23977 3.53179 4.04407 3.33609L0.854995 0.14701C0.659297 -0.0486879 0.342008 -0.0486879 0.146311 0.14701C-0.0493872 0.342707 -0.0493872 0.659996 0.146311 0.855694L2.98105 3.69043L0.146311 6.52517C-0.0493872 6.72086 -0.0493872 7.03815 0.146311 7.23385C0.342008 7.42955 0.659297 7.42955 0.854995 7.23385L4.04407 4.04477ZM2.6875 3.69043V4.19154H3.68973V3.69043V3.18931H2.6875V3.69043Z"/>
                </svg>
            </a>
        @endif

    </div>

    <div class="recommended-featured-cards">
        @foreach(($articles ?? collect()) as $article)
            <x-cards.card-component option="text" :article="$article" :isVisible="true" />
        @endforeach
    </div>
</div>