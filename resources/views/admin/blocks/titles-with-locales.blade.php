@if (!empty($locales))
    <div class="titles-column">
        @foreach ($locales as $locale)
            <div class="title-row">
                @if (!empty($locale['icon_url']))
                    <img
                        class="locale-flag"
                        src="{{ $locale['icon_url'] }}"
                        alt="{{ $locale['name'] }}"
                        title="{{ $locale['name'] }}"
                    >
                @else
                    <span class="locale-badge">
                        {{ strtoupper($locale['code']) }}
                    </span>
                @endif

                @if (!empty($locale['url']))
                    <a
                        class="title-text"
                        href="{{ $locale['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {{ $locale['title'] }}
                    </a>
                @else
                    <span class="title-text">
                        {{ $locale['title'] }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>
@else
    -
@endif
