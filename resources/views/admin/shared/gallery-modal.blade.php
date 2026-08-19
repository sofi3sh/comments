<script>
    window.editorGalleryConfig = Object.assign(window.editorGalleryConfig || {}, {
        maxImages: @json(config('editor.max_gallery_images')),
        defaultAttachment: {
            id: null,
            is_default: true,
            url: @json(asset(config('app.default_cover'))),
            thumbnail_url: @json(asset(config('app.default_cover'))),
            filename: @json(basename(config('app.default_cover'))),
            alt: 'Default cover',
            title: 'Default cover',
            caption: '',
            mime_type: 'image/webp',
        },
    });
</script>
@vite('resources/js/editorjs.js')

<div id="gallery-modal" class="gallery-modal">
    <div class="gallery-modal-overlay"></div>
    <div class="gallery-modal-content">
        <div class="gallery-modal-header">
            <h2>Галерея</h2>
            <button type="button" class="gallery-modal-close">&times;</button>
        </div>
        <div class="gallery-modal-body">
            <div class="gallery-modal-toolbar">
                <div class="gallery-modal-search">
                    <input type="text"
                           id="gallery-search-input"
                           placeholder="Пошук по тегах..."
                           class="gallery-search-input">
                </div>
                @if(backpack_user() && backpack_user()->can('attachment.create'))
                <div class="gallery-modal-actions">
                    <label class="gallery-upload-button">
                        <input type="file" 
                               id="gallery-file-input" 
                               accept="image/*" 
                               style="display: none;">
                        <span>Завантажити</span>
                    </label>
                </div>
                @endif
            </div>
            <div class="gallery-modal-grid" id="gallery-grid">
                <div class="gallery-loading">Завантаження...</div>
            </div>
            <div class="gallery-modal-pagination" id="gallery-pagination"></div>
        </div>
        <div class="gallery-modal-footer">
            @if(backpack_user() && backpack_user()->can('attachment.create'))
            <div class="gallery-upload-form" id="gallery-upload-form" style="display: none;">
                <h3>Завантажити нове зображення</h3>
                <div class="gallery-upload-preview" id="gallery-upload-preview"></div>
                <div class="gallery-upload-fields">
                    <div class="form-group">
                        <label>Alt текст:</label>
                        <input type="text" id="gallery-upload-alt" placeholder="Alt текст">
                    </div>
                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" id="gallery-upload-title" placeholder="Title">
                    </div>
                    <div class="form-group">
                        <label>Підпис:</label>
                        <input type="text" id="gallery-upload-caption" placeholder="Підпис">
                    </div>
                    <div class="form-group gallery-upload-tags-group">
                        <label>Теги статтей:</label>
                        <select id="gallery-upload-tags-select"
                                class="gallery-tag-select"
                                multiple>
                        </select>
                    </div>
                    <div id="gallery-upload-error" class="gallery-upload-error" style="display:none;"></div>
                </div>
                <div class="gallery-upload-actions">
                    <button type="button" class="gallery-button gallery-button-primary" id="gallery-upload-submit">Завантажити</button>
                    <button type="button" class="gallery-button" id="gallery-upload-cancel">Скасувати</button>
                </div>
            </div>
            @endif
            <div class="gallery-modal-footer-actions">
                <button type="button" class="gallery-button gallery-button-danger" id="gallery-modal-cancel">Скасувати</button>
                <button type="button" class="gallery-button gallery-button-primary" id="gallery-select-button" disabled>Вибрати</button>
            </div>
        </div>
    </div>
</div>
