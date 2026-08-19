/** Gallery Editor.js tool. Supports old single-image data and new multi-image data. */
const MAX_IMAGES = Number(window.editorGalleryConfig?.maxImages);

export default class GalleryTool {
    static get toolbox() {
        return {
            title: 'Gallery',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="m5 16 4-4 3 3 3-4 4 5" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="8" r="1.5" fill="currentColor"/></svg>'
        };
    }

    static get isReadOnlySupported() { return true; }

    constructor({ data, readOnly }) {
        this.readOnly = readOnly;
        this.data = {
            images: Array.isArray(data?.images)
                ? data.images.slice(0, MAX_IMAGES)
                : data?.attachment_id ? [data] : [],
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'image-gallery-tool';
        this._render();
        return this.wrapper;
    }

    _render() {
        this.wrapper.innerHTML = '';
        if (!this.data.images.length) {
            this.wrapper.append(this._button('Вибрати зображення з медіатеки', 'image-gallery-tool__add', () => this._openPicker()));
            return;
        }

        const list = document.createElement('div');
        list.className = 'image-gallery-tool__list';
        this.data.images.forEach((image, index) => {
            const item = document.createElement('div');
            item.className = 'image-gallery-tool__item';
            const imageMarkup = image.url
                ? `<img src="${this._escape(image.url)}" alt="${this._escape(image.alt || '')}">`
                : '<div class="image-gallery-tool__placeholder"></div>';
            item.innerHTML = `${imageMarkup}<div class="image-gallery-tool__fields"><strong>${this._escape(image.title || image.filename || `Зображення #${image.attachment_id}`)}</strong></div>`;
            if (!this.readOnly) {
                const controls = document.createElement('div');
                controls.className = 'image-gallery-tool__controls';
                [['↑', () => this._move(index, -1)], ['↓', () => this._move(index, 1)], ['×', () => { this.data.images.splice(index, 1); this._render(); }]].forEach(([label, handler]) => controls.append(this._button(label, '', handler)));
                item.append(controls);
            }
            list.append(item);
            if (!image.url && !image.loading) this._hydrateImage(image);
        });
        this.wrapper.append(list);
        if (!this.readOnly && this.data.images.length < MAX_IMAGES) {
            this.wrapper.append(this._button('Додати з медіатеки', 'image-gallery-tool__add', () => this._openPicker()));
        }
    }

    _button(label, className, handler) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = label;
        button.addEventListener('click', handler);
        return button;
    }

    _openPicker() {
        GalleryTool.openGalleryModal({
            multiple: true,
            maxSelection: MAX_IMAGES - this.data.images.length,
            allowUpload: false,
            onSelect: attachments => {
                this.data.images.push(...attachments.map(attachment => ({ ...attachment, attachment_id: attachment.id })));
                this._render();
            },
        });
    }

    _move(index, direction) {
        const next = index + direction;
        if (next < 0 || next >= this.data.images.length) return;
        [this.data.images[index], this.data.images[next]] = [this.data.images[next], this.data.images[index]];
        this._render();
    }

    _escape(value) { const node = document.createElement('div'); node.textContent = value || ''; return node.innerHTML; }

    async _hydrateImage(image) {
        image.loading = true;
        try {
            const response = await fetch(`/api/attachments/${image.attachment_id}`, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (response.ok && payload.success && payload.data) {
                Object.assign(image, payload.data);
                this._render();
            }
        } catch (_) { /* The attachment may have been removed from the media library. */ }
        finally { delete image.loading; }
    }

    static getArticleTags() {
        const select = document.querySelector('select[name="tags[]"], select[name="tags"]');
        if (!select) return [];
        const selected = Array.from(select.selectedOptions);
        return selected.map(option => ({ id: option.value, name: option.text }));
    }

    static openGalleryModal(options = {}) {
        const { onSelect, articleTags, multiple = false, maxSelection = 1, allowUpload = true } = options;
        if (typeof onSelect !== 'function') return;
        const open = () => window.GalleryModal?.open({
            onSelect,
            articleTags: articleTags || GalleryTool.getArticleTags(),
            multiple,
            maxSelection,
            allowUpload,
        });
        if (window.GalleryModal?.open) open();
        else setTimeout(open, 100);
    }

    save() {
        const images = this.data.images
            .map(image => ({
                attachment_id: image.attachment_id,
                is_default: Boolean(image.is_default),
                url: image.url || '',
                alt: image.alt || '',
                title: image.title || '',
                caption: image.caption || '',
            }))
            .filter(image => image.attachment_id || image.is_default);

        const first = images[0] || {};

        return {
            attachment_id: first.attachment_id,
            url: first.url || '',
            alt: first.alt || '',
            title: first.title || '',
            caption: first.caption || '',
            images,
        };
    }
    static get sanitize() { return { images: {} }; }
}
