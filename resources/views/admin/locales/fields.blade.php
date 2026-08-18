@foreach ($fieldsConfig as $fieldName => $fieldConfig)

    @php

        $fieldId = "translations[$localeKey][$fieldName]";;
        $inputName = "translations[$localeKey][$fieldName]";
        $fieldLabel = $fieldConfig['label'];
        $fieldType = $fieldConfig['type'];
        $fieldOptions = $fieldConfig['options'] ?? [];
        $characterCounterEnabled = $fieldPrefix === 'translations' && in_array($fieldName, ['title', 'excerpt', 'content'], true);
        $characterLimit = $characterCounterEnabled ? (int) ($fieldConfig['max_length'] ?? 0) : null;
        $localesArr = $locales->pluck('code')->toArray();
        $fieldDefaultValue = $fieldPrefix === 'translations' && $fieldName === 'content'
            ? ($existingTranslation?->content_for_editor ?? $existingTranslation?->content ?? '')
            : ($existingTranslation?->$fieldName ?? '');

        $fieldValueOld = old(
            "translations.$localeKey.$fieldName",
            $fieldDefaultValue
        );
    @endphp

    @if ($fieldType === 'editorjs')
        @php
            $height = $fieldOptions['height'] ?? 500;
            $galleryOnly = $fieldOptions['gallery_only'] ?? false;
        @endphp

        <div class="form-group col-md-12 mb-3">
            <label for="{{$inputName}}">{{ $fieldConfig['label'] }}</label>

            @if($galleryOnly)
                <div class="gallery-only-wrapper">
                    <div class="gallery-preview-container" id="{{ $fieldId }}_preview" style="display: none;">
                        <div class="gallery-preview-image-wrapper">
                            <img src="" alt="" class="gallery-preview-image" id="{{ $fieldId }}_preview_img">
                            <button
                                    type="button"
                                    class="btn btn-sm btn-danger gallery-remove-button"
                                    data-editor-id="{{ $fieldId }}_editorjs"
                                    data-input-id="{{ $fieldId }}"
                                    title="{{__('Delete')}}"
                            >
                                <i class="la la-times"></i>
                            </button>
                        </div>
                    </div>
                    <button
                            type="button"
                            class="btn btn-primary gallery-select-button"
                            data-editor-id="{{ $fieldId }}_editorjs"
                            data-input-id="{{ $fieldId }}"
                            data-preview-id="{{ $fieldId }}_preview"
                            data-text-select="{{ __('article.buttons.select_file') }}"
                            data-text-change="{{ __('article.buttons.change_file') }}"
                    >
                        <i class="la la-image"></i> <span class="gallery-button-text">{{ __('article.buttons.select_file') }}</span>
                    </button>
                </div>
            @endif

            <div
                    id="{{ $inputName }}_editorjs"
                    class="editorjs-container"
                    data-input-id="{{ $fieldId }}-input"
                    @if($galleryOnly) data-gallery-only="true" style="display: none;" @endif
            ></div>

            <input
                    type="hidden"
                    name="{{ $inputName }}"
                    id="{{ $fieldId }}-input"
                    value="{{ $fieldValueOld }}"
                    data-debug-field="{{ $fieldName }}"
                    data-debug-locale="{{ $localeKey }}"
                    @if($characterCounterEnabled) data-character-counter data-character-counter-format="editorjs" data-character-limit="{{ $characterLimit }}" @endif
            >
            @if($characterCounterEnabled)
                <div class="translation-character-counter" data-character-counter-output></div>
            @endif

            @if(
                $fieldPrefix === 'translations'
                && $fieldName === 'content'
                && $existingTranslation?->id
                && request()->route('type')
                && in_array($localeKey, $localesArr, true)
            )
                <div
                        class="article-content-uniqueness mt-2"
                        data-content-uniqueness-url="{{ route('article.content-uniqueness.show', [
                            'type' => request()->route('type'),
                            'id' => $existingTranslation->article_id,
                            'locale' => $localeKey,
                        ]) }}"
                        data-content-uniqueness-recheck-url="{{ route('article.content-uniqueness.recheck', [
                            'type' => request()->route('type'),
                            'id' => $existingTranslation->article_id,
                            'locale' => $localeKey,
                        ]) }}"
                        data-csrf-token="{{ csrf_token() }}"
                        data-message-loading="{{ __('article-content.uniqueness.loading') }}"
                        data-message-recheck-loading="{{ __('article-content.uniqueness.recheck_loading') }}"
                        data-message-empty="{{ __('article-content.uniqueness.empty') }}"
                        data-message-error="{{ __('article-content.uniqueness.error') }}"
                        data-label-status="{{ __('article-content.uniqueness.status') }}"
                        data-label-uniqueness="{{ __('article-content.uniqueness.uniqueness') }}"
                        data-label-checked-at="{{ __('article-content.uniqueness.checked_at') }}"
                        data-label-error="{{ __('article-content.uniqueness.error_label') }}"
                        data-label-matches="{{ __('article-content.uniqueness.matches') }}"
                        data-label-recheck="{{ __('article-content.uniqueness.recheck_button') }}"
                >
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-content-uniqueness-show>
                        <i class="la la-search"></i>
                        {{ __('article-content.uniqueness.show_button') }}
                    </button>
                    <div class="article-content-uniqueness-result mt-2" data-content-uniqueness-result hidden></div>
                </div>
            @endif

        </div>
    @elseif ($fieldType === 'textarea')
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $fieldId }}">{{ $fieldConfig['label'] }}</label>
            <textarea
                    name="{{ $inputName }}"
                    id="{{ $fieldId }}"
                    class="form-control"
                    rows="5"
                    placeholder="{{ $fieldConfig['label'] }}"
                    @if($characterCounterEnabled) data-character-counter data-character-limit="{{ $characterLimit }}" @endif
            >{{ $existingTranslation ? ($existingTranslation->$fieldName ?? '') : $fieldValueOld }}</textarea>
            @if($characterCounterEnabled)
                <div class="translation-character-counter" data-character-counter-output></div>
            @endif
        </div>
    @else
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $fieldId }}">{{ $fieldConfig['label'] }}</label>
            <input
                    type="text"
                    name="{{ $inputName }}"
                    id="{{ $fieldId }}"
                    class="form-control"
                    placeholder="{{ $fieldConfig['label'] }}"
                    value="{{ $existingTranslation ? ($existingTranslation->$fieldName ?? '') : $fieldValueOld }}"
                    @if($characterCounterEnabled) data-character-counter data-character-limit="{{ $characterLimit }}" @endif
            >
            @if($characterCounterEnabled)
                <div class="translation-character-counter" data-character-counter-output></div>
            @endif
        </div>
    @endif
@endforeach
