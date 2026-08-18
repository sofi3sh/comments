@php

    $fieldLabel = $fieldConfig['label'];
    $fieldType = $fieldConfig['type'];
    $fieldOptions = $fieldConfig['options'] ?? [];

    $cover = $article->exists ? $article->thumbnailAttachment()->first() : null;

    if ($cover) {
        // Convert URL to editor.js JSON format
        $fieldValue = json_encode([
            'time' => time(),
            'blocks' => [
                [
                    'type' => 'gallery',
                    'data' => [
                        'attachment_id' => $cover->id,
                        'url' => $cover->url,
                        'alt' => $cover->alt ?? '',
                        'title' => $cover->title ?? '',
                        'caption' => $cover->caption ?? '',
                    ]
                ]
            ],
            'version' => '2.24.3'
        ]);
    } else {
        $fieldValue = '';
    }
@endphp

@php
    $height = $fieldOptions['height'] ?? 500;
    $galleryOnly = $fieldOptions['gallery_only'] ?? false;
@endphp

<div class="form-group col-md-12 mb-3">
    <label for="{{ $prefix }}{{ $fieldName }}">{{ $fieldConfig['label'] }}</label>

    @if($galleryOnly)
        <div class="gallery-only-wrapper">
            <div class="gallery-preview-container" id="{{ $prefix }}{{ $fieldName }}_preview" style="display: none;">
                <div class="gallery-preview-image-wrapper">
                    <img src="" alt="" class="gallery-preview-image" id="{{ $prefix }}{{ $fieldName }}_preview_img">
                    <button
                            type="button"
                            class="btn btn-sm btn-danger gallery-remove-button"
                            data-editor-id="{{ $prefix }}{{ $fieldName }}_editorjs"
                            data-input-id="{{ $prefix }}{{ $fieldName }}"
                            title="Видалити"
                    >
                        <i class="la la-times"></i>
                    </button>
                </div>
            </div>
            <button
                    type="button"
                    class="btn btn-primary gallery-select-button"
                    data-editor-id="{{ $prefix }}{{ $fieldName }}_editorjs"
                    data-input-id="{{ $prefix }}{{ $fieldName }}"
                    data-preview-id="{{ $prefix }}{{ $fieldName }}_preview"
                    data-text-select="{{ __('article.buttons.select_file') }}"
                    data-text-change="{{ __('article.buttons.change_file') }}"
            >
                <i class="la la-image"></i> <span class="gallery-button-text">{{ __('article.buttons.select_file') }}</span>
            </button>
        </div>
    @endif

    <div
            id="{{ $prefix }}{{ $fieldName }}_editorjs"
            class="editorjs-container"
            data-input-id="{{ $prefix }}{{ $fieldName }}"
            @if($galleryOnly) data-gallery-only="true" style="display: none;" @endif
    ></div>

    <input
            type="hidden"
            name="{{ $prefix }}{{ $fieldName }}"
            id="{{ $prefix }}{{ $fieldName }}"
            value="{{ $fieldValue }}"
            data-debug-field="{{ $fieldName }}"

    >

</div>
