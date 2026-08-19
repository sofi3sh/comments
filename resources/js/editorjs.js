import EditorJS from '@editorjs/editorjs';
import Paragraph from '@editorjs/paragraph';
import Header from '@editorjs/header';
import Quote from '@editorjs/quote';
import Code from '@editorjs/code';
import Delimiter from '@editorjs/delimiter';
import Marker from '@editorjs/marker';
import InlineCode from '@editorjs/inline-code';
import LinkTool from '@editorjs/link';
import Warning from '@editorjs/warning';
import Embed from '@editorjs/embed';
import GalleryTool from './editorjs-gallery-tool.js';

// Import gallery modal CSS and JS
import '../css/gallery-modal.css';
import '../css/editorjs-quote.css';
import './gallery-modal.js';

(function () {
    'use strict';

    let tools = {};
    let toolsLoaded = false;
    const textInlineToolbar = ['bold', 'italic', 'link', 'marker', 'inlineCode'];

    // Initialize tools object
    function initTools() {
        tools = {
            paragraph: {
                class: Paragraph,
                inlineToolbar: textInlineToolbar,
            },
            header: {
                class: Header,
                inlineToolbar: textInlineToolbar,
                config: {
                    levels: [1, 2, 3, 4, 5, 6],
                    defaultLevel: 2
                }
            },
            gallery: {
                class: GalleryTool,
            },
            quote: {
                class: Quote,
                inlineToolbar: textInlineToolbar,
                config: {
                    quotePlaceholder: 'Введіть цитату'
                }
            },
            code: {
                class: Code,
                config: {
                    placeholder: 'Введіть код'
                }
            },
            delimiter: {
                class: Delimiter
            },
            marker: {
                class: Marker
            },
            inlineCode: {
                class: InlineCode
            },
            linkTool: {
                class: LinkTool,
                config: {
                    endpoint: '/api/link',
                    shortcut: 'CMD+L',
                    target: '_blank',
                    rel: 'nofollow'
                }
            },
            warning: {
                class: Warning,
                inlineToolbar: textInlineToolbar,
                config: {
                    titlePlaceholder: 'Заголовок',
                    messagePlaceholder: 'Повідомлення'
                }
            },
            embed: {
                class: Embed,
                config: {
                    services: {
                        youtube: true,
                        coub: true,
                        codepen: true,
                        vimeo: true
                    }
                }
            }
        };

        toolsLoaded = true;
    }

    // Initialize tools immediately
    initTools();


    function normalizeLegacyBlocks(data) {
        if (!data || !Array.isArray(data.blocks)) {
            return data;
        }

        data.blocks = data.blocks.map((block) => {
            if (!block || block.type !== 'highlightedText') {
                return block;
            }

            return {
                ...block,
                type: 'quote',
                data: {
                    text: block.data?.text || '',
                    caption: block.data?.caption || '',
                    alignment: block.data?.alignment || 'left',
                },
            };
        });

        return data;
    }
    function updateButtonText(button, text, iconClass) {
        if (!button) return;
        
        // Clear button content
        button.textContent = '';
        
        // Create icon element
        const icon = document.createElement('i');
        icon.className = iconClass || 'la la-image';
        button.appendChild(icon);
        
        // Create text span
        const textSpan = document.createElement('span');
        textSpan.className = 'gallery-button-text';
        textSpan.textContent = ' ' + text;
        button.appendChild(textSpan);
    }

    function currentGalleryButtonText(button) {
        return button?.querySelector('.gallery-button-text')?.textContent.trim() || '';
    }

    /**
     * Update gallery preview
     */
    function updateGalleryPreview(inputId, attachment) {
        const selectButton = document.querySelector(`.gallery-select-button[data-input-id="${inputId}"]`);
        const previewContainer = selectButton ? document.getElementById(selectButton.getAttribute('data-preview-id')) : null;
        const previewImg = previewContainer ? previewContainer.querySelector('.gallery-preview-image') : null;
        
        if (previewContainer && previewImg && attachment?.url) {
            previewImg.src = attachment.url;
            previewImg.alt = attachment.alt || '';
            previewContainer.style.display = 'block';
            if (selectButton) {
                const changeText = selectButton.getAttribute('data-text-change') || currentGalleryButtonText(selectButton);
                updateButtonText(selectButton, changeText, 'la la-edit');
            }
            return;
        }

        clearGalleryPreview(inputId);
    }

    // Keep the hidden input in sync with EditorJS. Notify form listeners for real editor changes,
    // but allow silent writes before submit/autosave to avoid recursive save events.
    function setInputValue(input, value, notify = true) {
        if (!input) return;

        input.value = value;

        if (!notify) {
            return;
        }

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function clearGalleryPreview(inputId) {
        const selectButton = document.querySelector(`.gallery-select-button[data-input-id="${inputId}"]`);
        const previewContainer = selectButton ? document.getElementById(selectButton.getAttribute('data-preview-id')) : null;
        const previewImg = previewContainer ? previewContainer.querySelector('.gallery-preview-image') : null;

        if (previewContainer) {
            previewContainer.style.display = 'none';
        }

        if (previewImg) {
            previewImg.removeAttribute('src');
            previewImg.alt = '';
        }

        if (selectButton) {
            const selectText = selectButton.getAttribute('data-text-select') || currentGalleryButtonText(selectButton);
            updateButtonText(selectButton, selectText, 'la la-image');
        }
    }

    function firstGalleryImageData(galleryData) {
        if (!galleryData) {
            return null;
        }

        if (Array.isArray(galleryData.images) && galleryData.images.length > 0) {
            return galleryData.images.find(image => image && (image.attachment_id || image.is_default)) || null;
        }

        return galleryData.attachment_id || galleryData.is_default ? galleryData : null;
    }

    async function fetchAttachmentDetails(id) {
        if (!id) {
            return null;
        }

        try {
            const response = await fetch(`/api/attachments/${id}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            return response.ok && payload.success ? payload.data : null;
        } catch (_) {
            return null;
        }
    }

    async function previewAttachmentFromGalleryData(inputId, galleryData) {
        const image = firstGalleryImageData(galleryData);

        if (!image) {
            clearGalleryPreview(inputId);
            return;
        }

        if (image.url) {
            updateGalleryPreview(inputId, image);
            return;
        }

        const attachment = await fetchAttachmentDetails(image.attachment_id);
        updateGalleryPreview(inputId, attachment);
    }

    function updateGalleryOnlyPreviewFromData(inputId, data) {
        const galleryBlock = data.blocks && data.blocks.find(block => block.type === 'gallery');

        if (galleryBlock && galleryBlock.data) {
            previewAttachmentFromGalleryData(inputId, galleryBlock.data);

            return;
        }

        clearGalleryPreview(inputId);
    }
    /**
     * Remove gallery
     */
    async function removeGallery(editorInstance, inputId) {
        if (!editorInstance) {
            // console.error('[EditorJS] Editor instance not found');
            return;
        }

        try {
            const saved = await editorInstance.save();
            const existingBlocks = saved.blocks || [];
            const galleryBlockIndex = existingBlocks.findIndex(block => block.type === 'gallery');
            
            if (galleryBlockIndex !== -1) {
                await editorInstance.blocks.delete(galleryBlockIndex);
                
                // Save editor content
                const updated = await editorInstance.save();
                const input = document.getElementById(inputId);
                if (input) {
                    setInputValue(input, JSON.stringify(updated));
                }
                
                // Hide preview and update button
                const selectButton = document.querySelector(`.gallery-select-button[data-input-id="${inputId}"]`);
                const previewContainer = selectButton ? document.getElementById(selectButton.getAttribute('data-preview-id')) : null;
                
                if (previewContainer) {
                    previewContainer.style.display = 'none';
                }
                if (selectButton) {
                    const selectText = selectButton.getAttribute('data-text-select') || currentGalleryButtonText(selectButton);
                    updateButtonText(selectButton, selectText, 'la la-image');
                }
            }
        } catch (error) {
            // console.error('[EditorJS] Error removing gallery:', error);
            alert('Помилка при видаленні галереї. Спробуйте ще раз.');
        }
    }

    /**
     * Load existing gallery preview
     */
    function loadExistingGalleryPreview(inputId) {
        const input = document.getElementById(inputId);
        if (!input) {
            // console.warn('[EditorJS] Input not found for preview:', inputId);
            return;
        }
        
        if (!input.value || input.value.trim() === '') {
            // console.log('[EditorJS] No value in input for preview:', inputId);
            return;
        }

        try {
            const data = JSON.parse(input.value);
            // console.log('[EditorJS] Parsed data for preview:', data);
            
            if (!data.blocks || !Array.isArray(data.blocks)) {
                // console.warn('[EditorJS] No blocks array in data');
                return;
            }
            
            const galleryBlock = data.blocks.find(block => block.type === 'gallery');
            
            if (galleryBlock && galleryBlock.data) {
                previewAttachmentFromGalleryData(inputId, galleryBlock.data);
            } else {
                console.log('[EditorJS] No gallery block found in data');
            }
        } catch (e) {
            // console.error('[EditorJS] Could not load existing gallery preview:', e);
            console.error('[EditorJS] Input value:', input.value);
        }
    }

    /**
     * Open gallery modal and add gallery block to editor
     */
    function openGalleryForEditor(editorInstance, inputId) {
        if (!editorInstance) {
            console.error('[EditorJS] Editor instance not found');
            return;
        }

        // Use GalleryTool static method to open gallery
        GalleryTool.openGalleryModal({
            onSelect: async (attachment) => {
                try {
                    // Check if gallery block already exists
                    const saved = await editorInstance.save();
                    const existingBlocks = saved.blocks || [];
                    const galleryBlockIndex = existingBlocks.findIndex(block => block.type === 'gallery');
                    
                    const galleryData = {
                        attachment_id: attachment.id || null,
                        is_default: Boolean(attachment.is_default),
                        url: attachment.url,
                        alt: attachment.alt || '',
                        title: attachment.title || '',
                        caption: attachment.caption || '',
                        images: [{
                            attachment_id: attachment.id || null,
                            is_default: Boolean(attachment.is_default),
                            url: attachment.url,
                            alt: attachment.alt || '',
                            title: attachment.title || '',
                            caption: attachment.caption || '',
                        }],
                    };
                    
                    if (galleryBlockIndex !== -1) {
                        // Replace existing gallery block
                        await editorInstance.blocks.delete(galleryBlockIndex);
                        await editorInstance.blocks.insert('gallery', galleryData, {}, galleryBlockIndex, true);
                    } else {
                        // Add new gallery block
                        await editorInstance.blocks.insert('gallery', galleryData);
                    }
                    
                    // console.log('[EditorJS] Gallery block added/updated successfully');
                    
                    // Save editor content
                    const updated = await editorInstance.save();
                    const input = document.getElementById(inputId);
                    if (input) {
                        setInputValue(input, JSON.stringify(updated));
                    }
                    
                    // Update preview
                    updateGalleryPreview(inputId, attachment);
                } catch (error) {
                    // console.error('[EditorJS] Error adding gallery block:', error);
                    alert('Помилка при додаванні галереї. Спробуйте ще раз.');
                }
            },
        });
    }

    function initEditorForContainer(container) {
        const editors = container.querySelectorAll(".editorjs-container:not([data-initialized])");

        editors.forEach(function (el) {
            const inputId = el.getAttribute("data-input-id");
            const input = document.getElementById(inputId);

            if (!input) {
                console.warn('[EditorJS] Input not found for ID:', inputId);
                return;
            }

            // console.log('[EditorJS] Initializing editor for:', inputId);

            let existingData = null;
            try {
                if (input.value && input.value.trim() !== '') {
                    existingData = normalizeLegacyBlocks(JSON.parse(input.value));
                    // console.log('[EditorJS] Parsed JSON data:', existingData);
                    // console.log('[EditorJS] Parsed data blocks count:', existingData.blocks ? existingData.blocks.length : 0);
                    
                    // Validate blocks and filter out invalid ones
                    if (existingData.blocks && Array.isArray(existingData.blocks)) {
                        const validBlocks = existingData.blocks.filter(block => {
                            if (!block || !block.type) {
                                console.warn('[EditorJS] Invalid block found:', block);
                                return false;
                            }
                            return true;
                        });
                        
                        if (validBlocks.length !== existingData.blocks.length) {
                            // console.warn('[EditorJS] Filtered out', existingData.blocks.length - validBlocks.length, 'invalid blocks');
                            existingData.blocks = validBlocks;
                        }
                    }
                } else {
                    existingData = {};
                    // console.log('[EditorJS] Empty input value, using empty data');
                }
            } catch (e) {
                // console.error('[EditorJS] JSON parse error:', e);
                // console.error('[EditorJS] Failed to parse value:', input.value);
                existingData = { blocks: [] };
            }

            if (el._editor) return;

            // Check if this is a gallery-only field
            const isGalleryOnly = el.getAttribute("data-gallery-only") === "true";

            let editorTools;
            if (isGalleryOnly) {
                // For gallery-only fields, only include gallery tool
                editorTools = {
                    gallery: tools.gallery
                };
                // console.log('[EditorJS] Gallery-only mode enabled for:', inputId);
            } else {
                // Ensure paragraph tool is always available for regular editors
                if (!tools.paragraph) {
                    // console.error('[EditorJS] Paragraph tool is missing! This will cause errors.');
                    return;
                }
                editorTools = tools;
            }

            // console.log('[EditorJS] Initializing EditorJS with tools:', Object.keys(editorTools));
            // console.log('[EditorJS] Paragraph tool available:', !!editorTools.paragraph);
            // console.log('[EditorJS] Gallery tool available:', !!editorTools.gallery);

            const inlineTools = isGalleryOnly ? [] : textInlineToolbar;

            const editorInstance = new EditorJS({
                holder: el.id,
                data: existingData,
                tools: editorTools,
                placeholder: isGalleryOnly ? 'Натисніть, щоб обрати галерею...' : 'Почніть вводити текст...',
                inlineToolbar: inlineTools,
                onReady: function() {
                    // console.log('[EditorJS] Editor is ready for:', inputId);
                    
                    // For gallery-only fields, setup button handlers and load preview
                    if (isGalleryOnly) {
                        const editorElement = document.getElementById(el.id);
                        if (editorElement) {
                            // Setup button click handler
                            setTimeout(() => {
                                const selectButton = document.querySelector(`.gallery-select-button[data-editor-id="${el.id}"]`);
                                if (selectButton) {
                                    selectButton.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        openGalleryForEditor(el._editor, inputId);
                                    });
                                }
                                
                                // Setup remove button handler
                                const removeButton = document.querySelector(`.gallery-remove-button[data-editor-id="${el.id}"]`);
                                if (removeButton) {
                                    removeButton.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        removeGallery(el._editor, inputId);
                                    });
                                }
                                
                                // Load existing gallery preview
                                // Use longer timeout to ensure editor is fully initialized
                                setTimeout(() => {
                                    loadExistingGalleryPreview(inputId);
                                }, 100);
                            }, 200);
                        }
                    }
                },
                async onChange() {
                    if (!el._editor) return;
                    try {
                        const saved = await el._editor.save();
                        // console.log('[EditorJS] onChange triggered for:', inputId);
                        // console.log('[EditorJS] Saved blocks count:', saved.blocks ? saved.blocks.length : 0);
                        setInputValue(input, JSON.stringify(saved));
                        // console.log('[EditorJS] Input value updated, new length:', input.value.length);
                        
                        // Update preview for gallery-only fields
                        if (isGalleryOnly) {
                            const galleryBlock = saved.blocks && saved.blocks.find(block => block.type === 'gallery');
                            if (galleryBlock && galleryBlock.data) {
                                previewAttachmentFromGalleryData(inputId, galleryBlock.data);
                            } else {
                                clearGalleryPreview(inputId);
                            }
                        }
                    } catch (e) {
                        // console.error('[EditorJS] Save error:', e);
                        console.error('[EditorJS] Save error stack:', e.stack);
                    }
                }
            });

            el._editor = editorInstance;
            el.setAttribute("data-initialized", "true");
            
            // Log available tools after initialization
            editorInstance.isReady.then(() => {
                // console.log('[EditorJS] Editor initialized successfully for:', inputId);
                // console.log('[EditorJS] Available block tools:', Object.keys(editorTools));
                // console.log('[EditorJS] Paragraph tool class:', editorTools.paragraph ? editorTools.paragraph.class : 'MISSING');
            }).catch((error) => {
                // console.error('[EditorJS] Editor initialization error:', error);
                // console.error('[EditorJS] Error details:', error.message, error.stack);
                if (error.message && error.message.includes('paragraph')) {
                    // console.error('[EditorJS] Paragraph-related error detected!');
                    console.error('[EditorJS] Paragraph tool in config:', editorTools.paragraph);
                }
            });
        });
    }

    function initAllEditors() {
        if (!toolsLoaded) {
            // console.warn('[EditorJS] Tools not loaded yet, waiting...');
            setTimeout(initAllEditors, 100);
            return;
        }
        initEditorForContainer(document);
    }

    async function saveAllEditors() {
        const editors = document.querySelectorAll(".editorjs-container[data-initialized]");
        const savePromises = [];

        editors.forEach(function(el) {
            const inputId = el.getAttribute("data-input-id");
            const input = document.getElementById(inputId);

            if (input && el._editor) {
                savePromises.push(
                    el._editor.save().then(function(saved) {
                        setInputValue(input, JSON.stringify(saved), false);
                        return true;
                    }).catch(function(err) {
                        console.error('[EditorJS] Error saving editor:', inputId, err);
                        return false;
                    })
                );
            }
        });

        return Promise.all(savePromises);
    }

    async function setEditorData(inputId, value) {
        const input = document.getElementById(inputId);
        if (!input) return;

        let data = { blocks: [] };

        try {
            data = normalizeLegacyBlocks(value && value.trim() !== '' ? JSON.parse(value) : { blocks: [] });
        } catch (error) {
            console.error('[EditorJS] Could not parse translated data:', error);
        }

        data = normalizeLegacyBlocks(data);
        setInputValue(input, JSON.stringify(data));

        const editorElement = Array.from(
            document.querySelectorAll('.editorjs-container[data-input-id]')
        ).find(function (element) {
            return element.getAttribute('data-input-id') === inputId;
        });

        if (!editorElement || !editorElement._editor) {
            return;
        }

        try {
            await editorElement._editor.isReady;
            await editorElement._editor.render(data);

            if (editorElement.getAttribute('data-gallery-only') === 'true') {
                updateGalleryOnlyPreviewFromData(inputId, data);
            }
        } catch (error) {
            console.error('[EditorJS] Could not render translated data:', error);
        }
    }

    window.ArticleEditorJs = {
        initContainer: initEditorForContainer,
        saveAll: saveAllEditors,
        setData: setEditorData,
    };

    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(initAllEditors, 150);
        
        // Ensure all editor data is saved before form submission
        const forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                // console.log('[EditorJS] Form submit triggered');
                
                const editors = document.querySelectorAll(".editorjs-container[data-initialized]");
                
                if (editors.length > 0) {
                    e.preventDefault();
                    const form = this;
                    
                    saveAllEditors().then(function(results) {
                        // console.log('[EditorJS] All editors saved, submitting form');
                        form.submit();
                    }).catch(function(err) {
                        // console.error('[EditorJS] Error saving editors:', err);
                        form.submit();
                    });
                }
            });
        });
    });

})();
