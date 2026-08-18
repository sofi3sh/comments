<div class="two-thirds-container container">
    <div class="two-thirds-container__left">
        @if($type == 'dossier')
            <x-containers.dossiers-container-component />
        @elseif($type == 'article')
            <x-containers.article-container-component
                :article="$article"
                :read-more-url="$readMoreUrl"
                :read-more-title="$readMoreTitle"
            />
        @elseif($type != 'dossier' && $articles)
            <x-containers.significant-list-container-component :articles="$articles" :type="$type" :letter="$letter" />
        @endif
    </div>
    <div
        class="two-thirds-container__right"
        data-dynamic-fragment="articles-with-actions"
        data-fragment-url="{{ route('locale.fragments.articles-with-actions', ['locale' => app()->getLocale()]) }}"
    >
        <div class="two-thirds-container__loader" aria-hidden="true"></div>
    </div>
    <x-fragments.dynamic-fragment-loader />
</div>
