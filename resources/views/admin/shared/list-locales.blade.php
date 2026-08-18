@if (!empty($locales))
    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 5px;">
        @foreach ($locales as $locale)
            @if (!empty($locale['icon_url']))
                <img
                        src="{{ $locale['icon_url'] }}"
                        alt="{{ $locale['name'] }}"
                        title="{{ $locale['name'] }}"
                        style="width: 15px; height: 15px; vertical-align: middle;"
                >
            @else
                <span>{{ $locale['code'] }}</span>
            @endif
        @endforeach
    </div>
@endif