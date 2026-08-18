@if($hasData)

    <div class="main-container">
        <div  class="main-container__wrapper">
            <div class="main-container__left">
                <x-containers.recomended-featured-container-component option="featured" :articles="$aiArticles" />
            </div>
            <div class="main-container__right">
                <div class="main-container__right-top">
                    <x-cards.card-component option="detailed" :article="$firstArticle" />
                </div>
                @if($restArticles->isNotEmpty())
                    <div class="main-container__right-bottom">
                        @foreach($restArticles as $article)
                            <x-cards.card-component option="compact" :article="$article" />
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="main-container__mobile main-swiper swiper">
                <div class="swiper-wrapper">
                    @foreach ($articles as $article)
                        <div class="swiper-slide">
                            <x-cards.card-component option="detailed" :article="$article" />
                        </div>
                    @endforeach
                </div>
                <div class="main-swiper-pagination swiper-custom-pagination swiper-pagination"></div>
            </div>
        </div>
    </div>

@endif