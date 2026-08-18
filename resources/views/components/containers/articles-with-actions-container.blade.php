<div class="articles-with-actions">
   <div class="articles-with-actions__header">
        <strong class="articles-with-actions__header-title">
            {{ __('page.articles-with-actions.title') }}
        </strong>

       {{--   TODO commented temporary    --}}
{{--        <div class="articles-with-actions__header-actions">--}}
{{--            <button class="articles-with-actions__header-actions-button articles-with-actions__header-actions-button--active">--}}
{{--                {{ __('page.articles-with-actions.last') }}--}}
{{--            </button>--}}
{{--            <button class="articles-with-actions__header-actions-button">--}}
{{--                {{ __('page.articles-with-actions.top') }}--}}
{{--            </button>--}}
{{--            <button class="articles-with-actions__header-actions-button">--}}
{{--                {{ __('page.articles-with-actions.exclusive') }}--}}
{{--            </button>--}}
{{--        </div>--}}
   </div>


   <div class="articles-with-actions__list">
        @foreach($articles as $article)
            <x-cards.compact-text-card-component :article="$article" />
        @endforeach
    </div>

    <div class="articles-with-actions__footer">
        <a href="{{ $listUrl }}" class="articles-with-actions__footer-button">
            {{ __('page.articles-with-actions.all') }}
        </a>
    </div>
</div>