<div class="search search--hide">
    <div class="search__overlay"></div>
    
    <div class="search__content">
       <div class="search__content-container">
           <div id="search_form"
                class="search__form"
                data-min="{{ __('search.min_chars', ['min' => 2]) }}">
                <button class="search__form-button">
                    <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.199255 19.8575L5.62111 14.5223C4.2013 12.9797 3.32892 10.9396 3.32892 8.69471C3.32961 3.89245 7.2851 0 12.1648 0C17.0445 0 21 3.89245 21 8.69471C21 13.497 17.0445 17.3894 12.1648 17.3894C10.0564 17.3894 8.12268 16.6602 6.60374 15.4478L1.16085 20.804C0.895639 21.0653 0.465078 21.0653 0.199873 20.804C0.136791 20.7424 0.0866528 20.6688 0.0524025 20.5876C0.0181522 20.5063 0.000480652 20.4191 0.000423431 20.3309C0.000364304 20.2427 0.0179234 20.1554 0.0520687 20.0741C0.0862122 19.9928 0.136253 19.9192 0.199255 19.8575ZM12.1648 16.0517C16.2936 16.0517 19.6406 12.7579 19.6406 8.69471C19.6406 4.63156 16.2936 1.33773 12.1648 1.33773C8.036 1.33773 4.68903 4.63156 4.68903 8.69471C4.68903 12.7579 8.036 16.0517 12.1648 16.0517Z" />
                    </svg>
                </button>
                <input id="search_input" class="search__form-input" type="text" placeholder="{{ __('page.header.search') }}">
           </div>

           <div id="search_title"
                class="search__title"
                data-all="{{ __('search.all_time') }}"
                data-recent="{{ __('search.recent', ['days' => config('search.recent_days')]) }}"
                data-error="{{ __('search.load_error') }}">
               <div class="search__title-text search__title-text--results" style="display:none;">
                   {{ __('search.results_for') }}
                   "<strong class="search__title-query"></strong>"
                   <span class="search__title-meta"></span>
               </div>

               <div class="search__title-text search__title-text--empty" style="display:none;">
                   {{ __('search.no_results_for') }}
                   "<strong class="search__title-query"></strong>"
               </div>

           </div>

           <div id="search_filter" class="search__filter search__filter--hidden">
               <button class="search__filter-btn search__filter-btn--active" data-filter="all">
                   {{__("All")}}
               </button>
               <button class="search__filter-btn" data-filter="recent">
                   {{__("Last")}}
               </button>
           </div>

           <div id="results"></div>
           <div id="pagination"  class="pagination"></div>

{{-- TODO commented temporary. should define content --}}
{{--           <div class="search__categories">--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item search__categories-main-item--active" href="#">News</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item search__categories-item--active" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item" href="#">Sport</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item" href="#">Business</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item" href="#">Inovation</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item" href="#">News</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item" href="#">Sport</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item" href="#">Business</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--                <ul class="search__categories-list">--}}
{{--                    <li><a class="search__categories-item search__categories-main-item" href="#">Inovation</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- War in Ukraine</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Latin American</a></li>--}}
{{--                    <li><a class="search__categories-item" href="#">- Middle East</a></li>--}}
{{--                </ul>--}}
{{--           </div>--}}
       </div>
    </div>
</div>

@push('scripts')

    <script>
        window.SEARCH_URL = "{{ route('api.search', ['locale' => app()->getLocale()]) }}";
    </script>

@endpush