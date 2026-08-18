@if($hasData)

    @php
        $articles = $page['articles'];
    @endphp

    <section class="latest-news container">
        <h2 class="latest-news__title headline">Latest News</h2>
        <div class="latest-news__container">
            @foreach ($articles as $article)
                <x-cards.card-component :option="'compact'" :article="$article" />
            @endforeach
        </div>
    </section>

@endif