<div class="mb-3" data-tab-prefix="{{ $fieldPrefix }}">
    @if($fieldPrefix === 'translations' && request()->route('type'))
        @php
            $targetDefaultLocale = $locales->firstWhere('code', '!=', $firstLocale)?->code ?? $firstLocale;
        @endphp

        <div
            class="card mb-3 article-auto-translate"
            data-auto-translate-url="{{ route('article.auto-translate', ['type' => request()->route('type')]) }}"
            data-csrf-token="{{ csrf_token() }}"
            data-message-loading="{{ __('article-translate.auto_translate.loading') }}"
            data-message-success="{{ __('article-translate.auto_translate.success') }}"
            data-message-skipped="{{ __('article-translate.auto_translate.skipped') }}"
            data-message-config-error="{{ __('article-translate.auto_translate.config_error') }}"
            data-message-same-locale="{{ __('article-translate.auto_translate.same_locale') }}"
            data-message-overwrite-confirm="{{ __('article-translate.auto_translate.overwrite_confirm') }}"
        >
            <div class="card-body">
                <div class="row align-items-end g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="article-auto-translate-target">
                            {{ __('article-translate.auto_translate.target_locale') }}
                        </label>
                        <select id="article-auto-translate-target" class="form-control" data-auto-translate-target>
                            @foreach($locales as $locale)
                                <option value="{{ $locale->code }}" @selected($locale->code === $targetDefaultLocale)>
                                    {{ $locale->name ?? strtoupper($locale->code) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <button type="button" class="btn btn-primary w-100" data-auto-translate-submit>
                            {{ __('article-translate.auto_translate.button') }}
                        </button>
                    </div>
                </div>

                <div class="small text-muted mt-2" data-auto-translate-status></div>
            </div>
        </div>
    @endif

    @include('admin.shared.locale-buttons', [
        'locales' => $locales,
        'firstLocale' => $firstLocale,
        'fieldPrefix' => $fieldPrefix,
        'ariaLabel' => $ariaLabel ?? 'Перемикання локалізацій',
        'switchFunction' => $switchFunction ?? 'switchLocaleTab',
    ])
    
    @include('admin.shared.locale-content', [
        'locales' => $locales,
        'firstLocale' => $firstLocale,
        'fieldsConfig' => $fieldsConfig,
        'fieldPrefix' => $fieldPrefix,
        'existingTranslations' => $existingTranslations,
        'fieldsView' => $fieldsView ?? null,
    ])
</div>

@if(isset($scriptView))
    @include($scriptView)
@endif
