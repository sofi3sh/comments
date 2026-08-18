@foreach ($fieldsConfig as $fieldName => $fieldConfig)

    @php
        $fieldId = "seo[$localeKey][$fieldName]";

        $fieldOldKey = "seo.$localeKey.$fieldName";

        $fieldValueOld = old(
            $fieldOldKey,
            $existingSeo?->$fieldName ?? ''
        );
    @endphp

    @if ($fieldConfig['type'] === 'upload')
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $fieldId }}">
                {{ $fieldConfig['label'] }}
            </label>
            <input
                    type="file"
                    name="{{ $fieldId }}"
                    id="{{ $fieldId }}"
                    class="form-control"
            >
        </div>

    @elseif ($fieldConfig['type'] === 'textarea')
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $fieldId }}">{{ $fieldConfig['label'] }}</label>
            <textarea
                    name="{{ $fieldId }}"
                    id="{{ $fieldId }}"
                    class="form-control"
                    rows="5"
                    placeholder="{{ $fieldConfig['label'] }}"
            >{{ $existingTranslation ? ($existingTranslation->$fieldName ?? '') : $fieldValueOld }}</textarea>
        </div>
    @else
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $fieldId }}">{{ $fieldConfig['label'] }}</label>
            <input
                    type="text"
                    name="{{ $fieldId }}"
                    id="{{ $fieldId }}"
                    class="form-control"
                    placeholder="{{ $fieldConfig['label'] }}"
                    value="{{ $existingTranslation ? ($existingTranslation->$fieldName ?? '') : $fieldValueOld }}"
            >
        </div>
    @endif

@endforeach