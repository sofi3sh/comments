@foreach ($fieldsConfig as $fieldName => $fieldConfig)
    @php
        $fieldId = $prefix . "{$fieldName}_{$localeKey}";
        $fieldLabel = $fieldConfig['label'];
        $fieldType = $fieldConfig['type'];
        $fieldOptions = $fieldConfig['options'] ?? [];
        $fieldValue = $existingTranslation ? ($existingTranslation->$fieldName ?? '') : '';
    @endphp

    @if ($fieldType === 'upload')
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}">{{ $fieldConfig['label'] }}</label>
            
            @if ($existingTranslation && $existingTranslation->$fieldName)
                @php
                    $fileUrl = asset('storage/' . $existingTranslation->$fieldName);
                    $fileExtension = strtolower(pathinfo($existingTranslation->$fieldName, PATHINFO_EXTENSION));
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
                    $isImage = in_array($fileExtension, $imageExtensions);
                @endphp
                
                @if ($isImage)
                    <div class="mb-2">
                        <a href="{{ $fileUrl }}" target="_blank" class="d-inline-block">
                            <img 
                                src="{{ $fileUrl }}" 
                                alt="{{ $fieldConfig['label'] }}" 
                                style="max-width: 150px; max-height: 150px; border: 1px solid #dee2e6; border-radius: 4px; padding: 4px;" 
                                class="img-thumbnail"
                            >
                        </a>
                    </div>
                @else
                    <div class="mb-2">
                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="la la-file"></i> {{ __('admin.view_file') }}
                        </a>
                    </div>
                @endif
            @endif
            
            <input 
                type="file" 
                name="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                id="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                class="form-control"
            >
        </div>
    @elseif ($fieldType === 'summernote')
        @php
            $height = $fieldOptions['height'] ?? 500;
        @endphp
        
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}">{{ $fieldConfig['label'] }}</label>
            <textarea 
                name="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                id="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                class="form-control summernote" 
                data-height="{{ $height }}" 
                rows="10" 
                placeholder="{{ $fieldConfig['label'] }}"
            >{{ $existingTranslation ? ($existingTranslation->$fieldName ?? '') : '' }}</textarea>
        </div>
    @elseif ($fieldType === 'textarea')
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}">{{ $fieldConfig['label'] }}</label>
            <textarea 
                name="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                id="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                class="form-control" 
                rows="5" 
                placeholder="{{ $fieldConfig['label'] }}"
            >{{ $existingTranslation ? ($existingTranslation->$fieldName ?? '') : '' }}</textarea>
        </div>
    @else
        <div class="form-group col-md-12 mb-3">
            <label for="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}">{{ $fieldConfig['label'] }}</label>
            <input 
                type="text" 
                name="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                id="{{ $prefix }}{{ $fieldName }}_{{ $localeKey }}" 
                class="form-control" 
                placeholder="{{ $fieldConfig['label'] }}" 
                value="{{ $existingTranslation ? ($existingTranslation->$fieldName ?? '') : '' }}"
            >
        </div>
    @endif
@endforeach

