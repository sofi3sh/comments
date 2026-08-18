@if($hasData)

    <section class="live container">
        <h2 class="live__title headline">Live</h2>

        @if($firstArticle)

            <div class="live-container__main">
                <x-cards.card-component option="detail-video" :article="$firstArticle" />
            </div>

            @if($restArticles->isNotEmpty())
                <div class="live-container__horizontal">
                    @foreach($restArticles as $article)
                        <x-cards.card-component option="video" :article="$article" />
                    @endforeach
                </div>
            @endif

        @endif

    </section>

@endif