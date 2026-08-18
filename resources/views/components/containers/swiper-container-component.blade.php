@if($hasData)

    @php
    $articles = $page['articles'];
    @endphp

    <div class="swiper-container swiper">
        <div class="swiper-container__wrapper">
            <div class="swiper-container__content">
                <div class="swiper-wrapper">
                    @foreach ($articles as $article)
                        <div class="swiper-slide">
                            <x-cards.card-component option="vertical" :article="$article" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="swiper-container-button-next swiper-custom-button-next"></div>
            <div class="swiper-container-button-prev swiper-custom-button-prev"></div>
            <div class="swiper-container-pagination swiper-custom-pagination"></div>
        </div>
    </div>

@endif
