@php
    $hasCategories = !empty($categories) && $categories->isNotEmpty();
    $hasTypes = !empty($types) && $types->isNotEmpty();
@endphp

<nav class="footer__categories" aria-label="{{ __('Footer navigation') }}">
    @if($hasCategories)
        <details class="footer__categories-group footer__categories-group--categories" open>
            <summary class="footer__categories-title">{{ __('Categories') }}</summary>
            <ul class="footer__categories-list">
                @foreach($categories as $item)
                    <li class="footer__categories-element">
                        @if($item->getSite())
                            <a href="{{ route('category.homepage', ['domain' => $item->getSite()->domain, 'locale' => app()->getLocale()]) }}">
                                {{ $item->translateOrDefault()?->name ?? $item->slug }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </details>
    @endif

    @if($hasTypes)
        <details class="footer__categories-group" open>
            <summary class="footer__categories-title">{{ __('Sections') }}</summary>
            <ul class="footer__categories-list">
                @foreach($types as $item)
                    <li class="footer__categories-element">
                        <a href="{{ route('locale.type.show.list', ['locale' => app()->getLocale(), 'type' => \App\Models\Articles\ArticleType::codeForRoute($item->code)]) }}">
                            {{ $item->name ?? $item->code }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif

    <details class="footer__categories-group" open>
        <summary class="footer__categories-title">{{ __('Regions') }}</summary>
        <ul class="footer__categories-list">
            <li class="footer__categories-element"><a href="#">Kharkiv</a></li>
            <li class="footer__categories-element"><a href="#">Kyiv</a></li>
            <li class="footer__categories-element"><a href="#">Odesa</a></li>
            <li class="footer__categories-element"><a href="#">Dnipro</a></li>
            <li class="footer__categories-element"><a href="#">Donbas</a></li>
        </ul>
    </details>

    <details class="footer__categories-group" open>
        <summary class="footer__categories-title">{{ __('Information') }}</summary>
        <ul class="footer__categories-list">
            <li class="footer__categories-element"><a href="#">{{ __('About the project') }}</a></li>
            <li class="footer__categories-element"><a href="#">{{ __('Advertising') }}</a></li>
            <li class="footer__categories-element"><a href="#">{{ __('Bloggers') }}</a></li>
            <li class="footer__categories-element"><a href="#">{{ __('News archive') }}</a></li>
        </ul>
    </details>
</nav>
