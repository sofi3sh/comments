@if($hasData)

    @php
        $leftArticles  = $page['leftArticles'];
        $leftArticlesCode = $page['leftArticlesCode'];
        $rightArticles = $page['rightArticles'];
        $rightArticlesCode = $page['rightArticlesCode'];
    @endphp

    <div class="articles-container container">
        <div class="articles-container__left">
            <x-containers.recomended-featured-container-component option="recommended"
                :articles="$leftArticles"
                :code="$leftArticlesCode"
            />
        </div>

        <div class="articles-container__right">
            <x-containers.consistent-container-component
                :articles="$rightArticles"
                :code="$rightArticlesCode"
            />
        </div>
    </div>

@endif