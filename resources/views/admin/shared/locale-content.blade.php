@foreach ($locales as $locale)
    @php
        $localeKey = $locale->code;
    @endphp
    <div 
        id="{{ $fieldPrefix }}-{{ $localeKey }}-content" 
        class="tab-content-wrapper" 
        data-tab-prefix="{{ $fieldPrefix }}" 
        @if($localeKey !== $firstLocale) style="visibility: hidden; position: absolute; left: -9999px; height: 0; overflow: hidden;" @endif
    >
        @include($fieldsView ?? 'admin.shared.locale-fields', [
            'fieldsConfig' => $fieldsConfig,
            'prefix' => $fieldPrefix . '_',
            'localeKey' => $localeKey,
            'existingTranslation' => $existingTranslations[$localeKey] ?? null,
        ])
    </div>
@endforeach

