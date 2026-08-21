
/**
 * Gallery Modal for selecting and uploading attachments
 */
class GalleryModal {
    constructor() {
        this.modal = null;
        this.currentPage = 1;
        this.searchQuery = '';
        this.selectedAttachment = null;
        this.selectedAttachments = new Map();
        this.multiple = false;
        this.maxSelection = 1;
        this.allowUpload = true;
        this.onSelectCallback = null;
        this.articleTags = [];
        this.isUploading = false;
        this.eventListenersAttached = false;
        this.defaultAttachment = window.editorGalleryConfig?.defaultAttachment || null;
        
        // Initialize modal when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this._initModal());
        } else {
            this._initModal();
        }
    }
    
    /**
     * Initialize modal from DOM
     */
    _initModal() {
        this.modal = document.getElementById('gallery-modal');
        if (this.modal) {
            this._attachEventListeners();
        }
    }

    /**
     * Open the gallery modal
     */
    open(options = {}) {
        this.onSelectCallback = options.onSelect || null;
        this.articleTags = options.articleTags || [];
        this.multiple = Boolean(options.multiple);
        this.maxSelection = Math.max(1, Number(options.maxSelection) || 1);
        this.allowUpload = options.allowUpload !== false;
        this.currentPage = 1;
        this.searchQuery = '';
        this.selectedAttachment = null;
        this.selectedAttachments.clear();

        this._createModal();
        this._loadAttachments();
        this._initTagSelect();
        this._setUploadVisibility();

        this._showModal();
    }

    /**
     * Close the gallery modal
     */
    close() {
        if (this.modal) {
            this.modal.classList.remove('gallery-modal-active');
            
            // Reset form
            const uploadForm = document.getElementById('gallery-upload-form');
            if (uploadForm) {
                uploadForm.style.display = 'none';
            }
            
            const fileInput = document.getElementById('gallery-file-input');
            if (fileInput) {
                fileInput.value = '';
            }
            
            const preview = document.getElementById('gallery-upload-preview');
            if (preview) {
                preview.innerHTML = '';
            }
            
            // Reset fields
            const altInput = document.getElementById('gallery-upload-alt');
            const titleInput = document.getElementById('gallery-upload-title');
            const captionInput = document.getElementById('gallery-upload-caption');
            if (altInput) altInput.value = '';
            if (titleInput) titleInput.value = '';
            if (captionInput) captionInput.value = '';
            
            this.searchQuery = '';
            this.selectedAttachment = null;
            this.selectedAttachments.clear();
            const searchInput = document.getElementById('gallery-search-input');
            if (searchInput) searchInput.value = '';
            const selectButton = document.getElementById('gallery-select-button');
            if (selectButton) {
                selectButton.disabled = true;
            }
        }
        document.body.style.overflow = '';
    }

    /**
     * Initialize modal from existing HTML in DOM
     */
    _createModal() {
        // Find existing modal in DOM (rendered from Blade template)
        if (!this.modal) {
            this.modal = document.getElementById('gallery-modal');
        }
        
        if (!this.modal) {
            console.error('Gallery modal HTML not found in DOM. Make sure gallery-modal.blade.php is included.');
            return;
        }

        // Reset modal state
        this.modal.classList.remove('gallery-modal-active');
        
        // Reset form fields
        const uploadForm = document.getElementById('gallery-upload-form');
        if (uploadForm) {
            uploadForm.style.display = 'none';
        }
        
        const fileInput = document.getElementById('gallery-file-input');
        if (fileInput) {
            fileInput.value = '';
        }

        this.searchQuery = '';
        const searchInput = document.getElementById('gallery-search-input');
        if (searchInput) searchInput.value = '';

        const selectButton = document.getElementById('gallery-select-button');
        if (selectButton) {
            selectButton.disabled = true;
        }

        // Attach event listeners if not already attached
        if (!this.eventListenersAttached) {
            this._attachEventListeners();
        }
        
        this._applyTheme();
        this._setUploadVisibility();
    }

    _setUploadVisibility() {
        const uploadButton = this.modal?.querySelector('.gallery-modal-actions');
        const uploadForm = document.getElementById('gallery-upload-form');
        if (uploadButton) uploadButton.style.display = this.allowUpload ? '' : 'none';
        if (uploadForm && !this.allowUpload) uploadForm.style.display = 'none';
    }

    /**
     * Attach event listeners
     */
    _attachEventListeners() {
        // Prevent duplicate event listeners
        if (this.eventListenersAttached) {
            return;
        }
        
        if (!this.modal) {
            return;
        }
        
        // Overlay click to close
        const overlay = this.modal.querySelector('.gallery-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.close();
            });
        }

        // Close button
        const closeButton = this.modal.querySelector('.gallery-modal-close');
        if (closeButton) {
            closeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.close();
            });
        }

        // Text search input
        const searchInput = document.getElementById('gallery-search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchQuery = (e.target.value || '').trim();
                    this.currentPage = 1;
                    this._loadAttachments();
                }, 300);
            });
        }

        // File input (only present when user has attachment.create permission)
        const fileInput = document.getElementById('gallery-file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this._handleFileSelect(e.target.files[0]);
                }
            });
        }

        // Upload submit
        const uploadSubmit = document.getElementById('gallery-upload-submit');
        if (uploadSubmit) {
            uploadSubmit.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this._uploadFile();
            });
        }

        // Select button
        const selectButton = document.getElementById('gallery-select-button');
        if (selectButton) {
            selectButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const selection = this.multiple
                    ? Array.from(this.selectedAttachments.values())
                    : this.selectedAttachment;
                if (selection && (!this.multiple || selection.length) && this.onSelectCallback) {
                    this.onSelectCallback(selection);
                    this.close();
                }
            });
        }

        // Cancel buttons
        const uploadCancelButton = document.getElementById('gallery-upload-cancel');
        if (uploadCancelButton) {
            uploadCancelButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                document.getElementById('gallery-upload-form').style.display = 'none';
            });
        }

        const modalCancelButton = document.getElementById('gallery-modal-cancel');
        if (modalCancelButton) {
            modalCancelButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.close();
            });
        }

        // Close on Escape key
        this._escapeHandler = (e) => {
            if (e.key === 'Escape' && this.modal && this.modal.classList.contains('gallery-modal-active')) {
                this.close();
            }
        };
        document.addEventListener('keydown', this._escapeHandler);
        
        // Recalculate sidebar width on window resize
        this._resizeHandler = () => {
            if (this.modal && this.modal.classList.contains('gallery-modal-active')) {
                this._calculateSidebarWidth();
            }
        };
        window.addEventListener('resize', this._resizeHandler);
        
        this.eventListenersAttached = true;
    }

    /**
     * Show modal
     */
    _showModal() {
        if (!this.modal) {
            return;
        }
        
        document.body.style.overflow = 'hidden';
        
        setTimeout(() => {
            if (this.modal) {
                this._calculateSidebarWidth();
                this.modal.classList.add('gallery-modal-active');
                this._applyTheme();
            }
        }, 10);
    }

    /**
     * Apply theme based on Backpack theme
     */
    _applyTheme() {
        const isDarkMode = document.body.getAttribute('data-bs-theme') === 'dark' || 
                          window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (this.modal) {
            if (isDarkMode) {
                this.modal.classList.add('gallery-modal-dark');
            } else {
                this.modal.classList.remove('gallery-modal-dark');
            }
        }
        
        // Calculate and set sidebar width for proper modal positioning
        this._calculateSidebarWidth();
    }
    
    /**
     * Calculate sidebar width and set CSS variable
     */
    _calculateSidebarWidth() {
        // Try to find sidebar element
        const sidebar = document.querySelector('.sidebar') || 
                       document.querySelector('.aside-menu') ||
                       document.querySelector('[class*="sidebar"]') ||
                       document.querySelector('[class*="aside"]');
        
        if (sidebar) {
            // Check if sidebar is visible and not collapsed
            const isVisible = sidebar.offsetWidth > 0 && 
                             sidebar.offsetHeight > 0 &&
                             window.getComputedStyle(sidebar).display !== 'none';
            
            // Check if sidebar is collapsed (common classes: collapsed, sidebar-hidden, etc.)
            const isCollapsed = sidebar.classList.contains('collapsed') ||
                               sidebar.classList.contains('sidebar-hidden') ||
                               sidebar.classList.contains('sidebar-compact') ||
                               document.body.classList.contains('sidebar-hidden');
            
            if (isVisible && !isCollapsed) {
                const sidebarWidth = sidebar.offsetWidth || sidebar.getBoundingClientRect().width;
                if (sidebarWidth > 0) {
                    document.documentElement.style.setProperty('--sidebar-width', `${sidebarWidth}px`);
                    return;
                }
            }
        }
        
        // Fallback: sidebar is hidden, collapsed, or not found - use 0 width
        document.documentElement.style.setProperty('--sidebar-width', '0px');
    }

    /**
     * Init Select2 tags selector
     */
    _initTagSelect() {

        const element = document.getElementById('gallery-upload-tags-select');

        if (!element || !window.$) {
            return;
        }

        const $element = window.$(element);

        // destroy old instance
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }

        $element.select2({
            width: '100%',

            placeholder: 'Введіть мінімум 3 символи...',

            multiple: true,

            minimumInputLength: 3,

            ajax: {

                delay: 300,

                url: '/api/tags/fetch',

                dataType: 'json',

                data: params => ({
                    q: params.term || ''
                }),

                processResults: data => ({
                    results: (data.data || []).map(item => ({
                        id: item.id,
                        text: item.display_name,
                    }))
                }),

                transport: (params, success, failure) => {

                    const csrfToken =
                        document.querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content')
                        ||
                        document.querySelector('input[name="_token"]')
                            ?.value
                        ||
                        '';

                    return $.ajax({
                        ...params,

                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },

                        success,
                        error: failure,
                    });
                }
            }
        });

        this.tagSelect = $element;
    }

    /**
     * Load attachments from API
     */
    async _loadAttachments() {
        const grid = document.getElementById('gallery-grid');
        if (!grid) return;

        grid.innerHTML = '<div class="gallery-loading">Завантаження...</div>';

        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: 20,
            });

            if (this.searchQuery) {
                params.append('search', this.searchQuery);
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                            document.querySelector('input[name="_token"]')?.value || '';

            const response = await fetch(`/api/attachments?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load attachments');
            }

            const data = await response.json();

            if (data.success && data.data) {
                this._renderAttachments(this._attachmentsForCurrentView(data.data));
                this._renderPagination(data.pagination);
            } else {
                grid.innerHTML = '<div class="gallery-error">Помилка завантаження</div>';
            }
        } catch (error) {
            grid.innerHTML = '<div class="gallery-error">Помилка завантаження: ' + error.message + '</div>';
        }
    }

    _attachmentsForCurrentView(attachments) {
        if (
            this.multiple ||
            this.searchQuery ||
            !this.defaultAttachment?.url
        ) {
            return attachments;
        }

        return [this.defaultAttachment, ...attachments];
    }

    _attachmentKey(attachment) {
        return attachment?.is_default ? '__default__' : String(attachment?.id || '');
    }

    /**
     * Render attachments grid
     */
    _renderAttachments(attachments) {
        const grid = document.getElementById('gallery-grid');
        if (!grid) return;

        if (attachments.length === 0) {
            grid.innerHTML = '<div class="gallery-empty">Немає зображень</div>';
            return;
        }

        grid.innerHTML = attachments.map(attachment => {
            const isImage = attachment.mime_type && attachment.mime_type.startsWith('image/');
            const attachmentKey = this._attachmentKey(attachment);
            const isSelected = this.multiple
                ? this.selectedAttachments.has(attachmentKey)
                : this.selectedAttachment && this._attachmentKey(this.selectedAttachment) === attachmentKey;
            // Use thumbnail_url if available, otherwise use url
            const imageUrl = attachment.thumbnail_url || attachment.url || '';

            return `
                <div class="gallery-item ${isSelected ? 'gallery-item-selected' : ''}" 
                     data-id="${attachmentKey}"
                     onclick="window.GalleryModal.selectAttachment('${attachmentKey}')">
                    ${isImage && imageUrl ? `
                        <img src="${imageUrl}" alt="${attachment.alt || ''}" class="gallery-item-image" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\\'gallery-item-icon\\'>📄</div>';">
                    ` : `
                        <div class="gallery-item-icon">📄</div>
                    `}
                    <div class="gallery-item-overlay">
                        <div class="gallery-item-info">
                            <div class="gallery-item-name">${attachment.filename}</div>
                            ${attachment.alt ? `<div class="gallery-item-alt">${attachment.alt}</div>` : ''}
                            ${attachment.is_public ? '<div class="gallery-item-alt">Публічне</div>' : ''}
                        </div>
                    </div>
                    ${isSelected ? '<div class="gallery-item-check">✓</div>' : ''}
                </div>
            `;
        }).join('');

        // Load attachment details for selected item
        if (this.selectedAttachment && !this.multiple) {
            this._loadAttachmentDetails(this.selectedAttachment.id);
        }
    }

    /**
     * Render pagination
     */
    _renderPagination(pagination) {
        const paginationEl = document.getElementById('gallery-pagination');
        if (!paginationEl || !pagination) return;

        if (pagination.last_page <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

        let html = '<div class="gallery-pagination">';
        
        if (pagination.current_page > 1) {
            html += `<button class="gallery-pagination-button" onclick="window.GalleryModal.goToPage(${pagination.current_page - 1})">‹ Попередня</button>`;
        }

        html += `<span class="gallery-pagination-info">Сторінка ${pagination.current_page} з ${pagination.last_page}</span>`;

        if (pagination.current_page < pagination.last_page) {
            html += `<button class="gallery-pagination-button" onclick="window.GalleryModal.goToPage(${pagination.current_page + 1})">Наступна ›</button>`;
        }

        html += '</div>';
        paginationEl.innerHTML = html;
    }

    /**
     * Go to specific page
     */
    goToPage(page) {
        this.currentPage = page;
        this._loadAttachments();
    }

    /**
     * Select attachment
     */
    async selectAttachment(id) {
        if (id === '__default__') {
            if (this.multiple || !this.defaultAttachment) {
                return;
            }

            this.selectedAttachment = this.defaultAttachment;
            this._updateSelection();
            return;
        }

        if (!this.multiple) {
            await this._loadAttachmentDetails(id);
            return;
        }

        const key = String(id);
        if (this.selectedAttachments.has(key)) {
            this.selectedAttachments.delete(key);
            this._updateSelection();
            return;
        }
        if (this.selectedAttachments.size >= this.maxSelection) {
            alert(`Можна вибрати не більше ${this.maxSelection} зображень`);
            return;
        }
        const attachment = await this._fetchAttachmentDetails(id);
        if (attachment) {
            this.selectedAttachments.set(this._attachmentKey(attachment), attachment);
            this._updateSelection();
        }
    }

    /**
     * Load attachment details
     */
    async _loadAttachmentDetails(id) {
        const attachment = await this._fetchAttachmentDetails(id);
        if (attachment) {
            this.selectedAttachment = attachment;
            this._updateSelection();
        }
    }

    async _fetchAttachmentDetails(id) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                            document.querySelector('input[name="_token"]')?.value || '';

            const response = await fetch(`/api/attachments/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load attachment details');
            }

            const data = await response.json();

            if (data.success && data.data) {
                return data.data;
            }
        } catch (error) {
            // Silent error handling
        }
        return null;
    }

    /**
     * Update selection UI
     */
    _updateSelection() {
        // Update grid items
        document.querySelectorAll('.gallery-item').forEach(item => {
            const itemId = item.getAttribute('data-id');
            const selected = this.multiple
                ? this.selectedAttachments.has(itemId)
                : this.selectedAttachment && itemId === this._attachmentKey(this.selectedAttachment);
            if (selected) {
                item.classList.add('gallery-item-selected');
            } else {
                item.classList.remove('gallery-item-selected');
            }
        });

        // Update select button
        const selectButton = document.getElementById('gallery-select-button');
        if (selectButton) {
            const count = this.multiple ? this.selectedAttachments.size : (this.selectedAttachment ? 1 : 0);
            selectButton.disabled = count === 0;
            selectButton.textContent = this.multiple ? `Вибрати (${count})` : 'Вибрати';
        }
    }

    /**
     * Handle file select for upload
     */
    _handleFileSelect(file) {
        const uploadForm = document.getElementById('gallery-upload-form');
        const preview = document.getElementById('gallery-upload-preview');
        
        if (!uploadForm || !preview) return;

        // Show preview
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 200px;">`;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `<div>Файл: ${file.name}</div>`;
        }

        uploadForm.style.display = 'block';
        uploadForm.dataset.file = file.name;
    }

    /**
     * Upload file
     */
    async _uploadFile() {
        const fileInput = document.getElementById('gallery-file-input');
        if (!fileInput || !fileInput.files.length) {
            alert('Будь ласка, виберіть файл');
            return;
        }

        if (this.isUploading) {
            return;
        }

        this.isUploading = true;
        const submitButton = document.getElementById('gallery-upload-submit');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Завантаження...';
        }

        try {
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('alt', document.getElementById('gallery-upload-alt')?.value || '');
            formData.append('title', document.getElementById('gallery-upload-title')?.value || '');
            formData.append('caption', document.getElementById('gallery-upload-caption')?.value || '');
            formData.append('is_public', document.getElementById('gallery-upload-is-public')?.checked ? '1' : '0');

            const values = this.tagSelect?.val?.() || [];
            const tagIds = (values instanceof Array ? values : [values]).filter(Boolean);

            if (tagIds.length === 0) {
                this._showUploadError('Оберіть хоча б один тег.');
                return;
            }

            tagIds.forEach(tagId => {
                formData.append('tag_ids[]', tagId);
            });

            // Add article tags for filename generation
            if (this.articleTags && this.articleTags.length > 0) {
                this.articleTags.forEach((tag, index) => {
                    formData.append(`article_tags[${index}][id]`, tag.id || '');
                    formData.append(`article_tags[${index}][name]`, tag.name || '');
                });
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                            document.querySelector('input[name="_token"]')?.value || '';

            const response = await fetch('/api/attachments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {

                const data = await response.json().catch(() => null);

                let message = 'Помилка завантаження';

                // Laravel validation errors (422)
                if (response.status === 422 && data?.errors) {
                    message = Object.values(data.errors)
                        .flat()
                        .join('<br>');
                }

                // fallback message
                else if (data?.message) {
                    message = data.message;
                }

                this._showUploadError(message);

                return;
            }

            const data = await response.json();

            if (data.success && data.data) {

                const error = document.getElementById('gallery-upload-error');
                if (error) {
                    error.style.display = 'none';
                    error.innerHTML = '';
                }

                // Reset form
                fileInput.value = '';
                document.getElementById('gallery-upload-alt').value = '';
                document.getElementById('gallery-upload-title').value = '';
                document.getElementById('gallery-upload-caption').value = '';
                const isPublicInput = document.getElementById('gallery-upload-is-public');
                if (isPublicInput) {
                    isPublicInput.checked = false;
                }
                document.getElementById('gallery-upload-form').style.display = 'none';
                document.getElementById('gallery-upload-preview').innerHTML = '';

                const tagSelect = document.getElementById('gallery-upload-tags-select');
                if (tagSelect) {
                    Array.from(tagSelect.options).forEach(opt => { opt.selected = false; });
                }

                // Reload attachments and select the new one
                await this._loadAttachments();
                await this._loadAttachmentDetails(data.data.id);
            } else {
                throw new Error('Upload failed');
            }
        } catch (error) {
            alert('Помилка завантаження: ' + error.message);
        } finally {
            this.isUploading = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Завантажити';
            }
        }
    }


    _showUploadError(message) {
        let container = document.getElementById('gallery-upload-error');

        if (!container) {
            container = document.createElement('div');
            container.id = 'gallery-upload-error';
            container.className = 'gallery-upload-error';
            document.querySelector('#gallery-upload-form')?.prepend(container);
        }

        container.innerHTML = message;
        container.style.display = 'block';
    }
}

// Initialize global instance immediately
// This ensures it's available as soon as the module loads
window.GalleryModal = new GalleryModal();
