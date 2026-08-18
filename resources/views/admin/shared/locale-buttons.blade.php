<div class="btn-group mb-3" role="group" aria-label="{{ $ariaLabel ?? 'Перемикання локалізацій' }}">
    @foreach ($locales as $locale)
        @php
            $localeKey = $locale->code;
            $localeName = $locale->name;
            $localeIcon = $locale->icon;
        @endphp
        <button 
            type="button" 
            class="{{ $localeKey === $firstLocale ? 'btn btn-primary active' : 'btn btn-outline-primary' }}" 
            data-locale-tab-button
            data-locale-key="{{ $localeKey }}"
            onclick="{{ $switchFunction ?? 'switchLocaleTab' }}('{{ $fieldPrefix }}-{{ $localeKey }}', this)"
        >
            @if($localeIcon)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($localeIcon) }}" alt="{{ $localeName }}" style="width: 16px; height: 16px; margin-right: 5px; vertical-align: middle;">
            @endif
            {{ $localeName }}
        </button>
    @endforeach
</div>
