(function () {
    'use strict';

    /**
     * Автосохранение публикаций:
     * - формы создания/редактирования публикаций сохраняются в localStorage по ключам articleAutosave:v1:*;
     * - кнопка создания сначала резервирует draft-публикацию на сервере, поэтому форма редактирования всегда работает с articleId;
     * - скрипт подключается только к админским article route/form, а не ко всем переводимым CRUD-формам;
     * - каждое input/change/select2-изменение через debounce 1 сек. сериализует все неслужебные поля формы;
     * - асинхронные сохранения упорядочены, чтобы старый save EditorJS не перезаписал более свежие данные;
     * - подписи выбранных <option> сохраняются, чтобы ajax-select2 восстанавливался с текстом, а не только с id;
     * - EditorJS и gallery-поля сохраняются через hidden inputs, включая JSON thumbnail с attachment_id;
     * - список сохранений хранится в articleAutosave:v1:index для глобальных уведомлений в админке;
     * - после успешного серверного сохранения flash article_autosave_success удаляет соответствующие локальные записи.
     */

    const STORAGE_PREFIX = 'articleAutosave:v1:';
    const INDEX_KEY = `${STORAGE_PREFIX}index`;
    const INTERNAL_FIELDS = new Set([
        '_token',
        '_method',
        '_http_referrer',
        '_save_action',
        '_current_tab',
        '_article_autosave_key',
    ]);
    const config = window.ArticleAutosaveConfig || {};
    const defaultMessages = {
        form_notice: 'У вас є збережені дані цієї публікації від :date.',
        global_notice: 'У вас є збережені дані для таких публікацій: [:items].',
        load: 'Завантажити',
        delete: 'Видалити',
        confirm_delete: 'Точно видалити?',
        details: 'Докладніше →',
        modal_title: 'Збережені дані публікацій',
        saved_at: 'Дата збереження',
        publication: 'Публікація',
        title: 'Заголовок',
        actions: 'Дії',
        edit: 'Редагувати',
        untitled: 'Без заголовка',
        new_publication: 'Нова публікація',
        close: 'Закрити',
    };
    const localizedMessages = config.messages && typeof config.messages === 'object' ? config.messages : {};
    const messages = Object.assign({}, defaultMessages, localizedMessages);

    let isRestoring = false;
    let saveSequence = 0;

    function message(key, replacements = {}) {
        let text = messages[key] || defaultMessages[key] || key;

        Object.entries(replacements).forEach(function ([name, value]) {
            text = text.split(`:${name}`).join(value);
        });

        return text;
    }

    function parseJson(value, fallback) {
        if (!value) {
            return fallback;
        }

        try {
            return JSON.parse(value);
        } catch (error) {
            return fallback;
        }
    }

    function getIndex() {
        const index = parseJson(localStorage.getItem(INDEX_KEY), []);

        if (Array.isArray(index)) {
            return index;
        }

        if (index && typeof index === 'object') {
            return Object.values(index);
        }

        return [];
    }

    function setIndex(index) {
        localStorage.setItem(INDEX_KEY, JSON.stringify(index));
    }

    function getRecord(storageKey) {
        return parseJson(localStorage.getItem(storageKey), null);
    }

    function storageKeyForMarker(marker) {
        const value = String(marker || '').trim();

        if (value === '') {
            return null;
        }

        if (value.startsWith(STORAGE_PREFIX)) {
            return value;
        }

        return `${STORAGE_PREFIX}${value}`;
    }

    function removeAutosave(marker) {
        const storageKey = storageKeyForMarker(marker);

        if (!storageKey) {
            return;
        }

        localStorage.removeItem(storageKey);

        const nextIndex = getIndex().filter(function (item) {
            if (!item) {
                return false;
            }

            if (typeof item === 'string') {
                return item !== storageKey && item !== String(marker);
            }

            return item.key !== storageKey
                && item.storageKey !== storageKey
                && String(item.id || '') !== String(marker);
        });

        setIndex(nextIndex);
    }

    function applySuccessMarkers() {
        const markers = config.successMarkers || {};

        Object.entries(markers).forEach(function ([marker, status]) {
            if (status === 'success') {
                removeAutosave(marker);
            }
        });
    }

    function debounce(fn, delay) {
        let timer;

        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn(...args);
            }, delay);
        };
    }


    function articlePathInfo() {
        const parts = window.location.pathname.split('/').filter(Boolean);
        const articleIndex = parts.indexOf('article');

        if (articleIndex === -1 || !parts[articleIndex + 1]) {
            return null;
        }

        const type = parts[articleIndex + 1];
        const numericPart = parts.find(function (part, index) {
            return index > articleIndex + 1 && /^\d+$/.test(part);
        });

        return {
            type,
            id: numericPart || null,
            isArticlePath: true,
        };
    }

    function formActionId(form) {
        if (!form.action) {
            return null;
        }

        try {
            const url = new URL(form.action, window.location.origin);
            const parts = url.pathname.split('/').filter(Boolean);
            const last = parts[parts.length - 1];

            return /^\d+$/.test(last) ? last : null;
        } catch (error) {
            return null;
        }
    }

    function isArticleForm(form) {
        if (!form || !form.matches('form')) {
            return false;
        }

        const info = articlePathInfo();
        if (!info || !form.action) {
            return false;
        }

        try {
            const action = new URL(form.action, window.location.origin);
            return action.pathname.split('/').filter(Boolean).includes('article');
        } catch (error) {
            return form.action.includes('/article/');
        }
    }

    function operationForForm(form) {
        const methodInput = form.querySelector('input[name="_method"]');
        const method = methodInput ? methodInput.value.toUpperCase() : form.method.toUpperCase();
        const info = articlePathInfo();
        const id = formActionId(form) || (info ? info.id : null);

        return {
            type: info ? info.type : null,
            id,
            mode: method === 'PUT' || method === 'PATCH' || id ? 'edit' : 'create',
        };
    }

    function ensureAutosaveKey(form, operation) {
        let input = form.querySelector('input[name="_article_autosave_key"]');

        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_article_autosave_key';
            form.appendChild(input);
        }

        if (operation.mode === 'edit' && operation.id) {
            input.value = storageKeyForMarker(operation.id);
            return input.value;
        }

        input.value = '';
        return null;
    }

    function shouldSkipField(element) {
        if (!element.name || element.disabled) {
            return true;
        }

        if (INTERNAL_FIELDS.has(element.name) || element.name.startsWith('_')) {
            return true;
        }

        return ['button', 'submit', 'reset', 'file'].includes((element.type || '').toLowerCase());
    }

    function addValue(target, name, value) {
        if (Object.prototype.hasOwnProperty.call(target, name)) {
            if (!Array.isArray(target[name])) {
                target[name] = [target[name]];
            }

            target[name].push(value);
            return;
        }

        target[name] = value;
    }

    function serializeForm(form) {
        const data = {};

        Array.from(form.elements).forEach(function (element) {
            if (shouldSkipField(element)) {
                return;
            }

            const type = (element.type || '').toLowerCase();

            if (type === 'radio') {
                if (element.checked) {
                    data[element.name] = element.value;
                } else if (!Object.prototype.hasOwnProperty.call(data, element.name)) {
                    data[element.name] = '';
                }

                return;
            }

            if (type === 'checkbox') {
                if (element.checked) {
                    addValue(data, element.name, element.value || '1');
                } else if (!Object.prototype.hasOwnProperty.call(data, element.name)) {
                    data[element.name] = element.name.endsWith('[]') ? [] : '';
                }

                return;
            }

            if (element.tagName === 'SELECT' && element.multiple) {
                data[element.name] = Array.from(element.selectedOptions).map(function (option) {
                    return option.value;
                });
                return;
            }

            addValue(data, element.name, element.value);
        });

        return data;
    }

    function selectedSelectOptions(form) {
        const optionsByField = {};

        Array.from(form.elements).forEach(function (element) {
            if (shouldSkipField(element) || element.tagName !== 'SELECT') {
                return;
            }

            const selected = Array.from(element.selectedOptions)
                .filter(function (option) {
                    return option.value !== '';
                })
                .map(function (option) {
                    return {
                        value: option.value,
                        text: option.textContent.trim(),
                    };
                });

            if (selected.length) {
                optionsByField[element.name] = selected;
            }
        });

        return optionsByField;
    }

    function titleByLocale(data) {
        const titles = {};
        const regex = /^translations\[([^\]]+)]\[title]$/;

        Object.entries(data).forEach(function ([name, value]) {
            const match = name.match(regex);
            if (match && typeof value === 'string' && value.trim() !== '') {
                titles[match[1]] = value.trim();
            }
        });

        return titles;
    }

    function upsertIndex(record) {
        const nextIndex = getIndex().filter(function (item) {
            return item && item.key !== record.key && item.storageKey !== record.key;
        });

        nextIndex.push({
            key: record.key,
            id: record.id,
            type: record.type,
            mode: record.mode,
            editUrl: record.editUrl,
            titleByLocale: record.titleByLocale,
            updatedAt: record.updatedAt,
        });

        setIndex(nextIndex);
    }

    async function saveEditors() {
        if (window.ArticleEditorJs && typeof window.ArticleEditorJs.saveAll === 'function') {
            await window.ArticleEditorJs.saveAll();
        }
    }

    async function saveForm(form) {
        if (isRestoring) {
            return;
        }

        const currentSequence = ++saveSequence;
        const operation = operationForForm(form);
        const storageKey = ensureAutosaveKey(form, operation);

        if (!storageKey) {
            return;
        }

        await saveEditors();

        if (currentSequence !== saveSequence) {
            return;
        }

        const data = serializeForm(form);
        const updatedAt = new Date().toISOString();
        const record = {
            key: storageKey,
            id: operation.id,
            type: operation.type,
            mode: operation.mode,
            editUrl: window.location.href,
            titleByLocale: titleByLocale(data),
            updatedAt,
            data,
            selectOptions: selectedSelectOptions(form),
        };

        localStorage.setItem(storageKey, JSON.stringify(record));
        upsertIndex(record);
    }

    function normalizeSavedValue(value) {
        if (Array.isArray(value)) {
            return value.map(String);
        }

        if (value === null || typeof value === 'undefined') {
            return [];
        }

        return [String(value)];
    }

    function triggerFieldChange(element) {
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));

        if (window.jQuery) {
            window.jQuery(element).trigger('change');
        }
    }

    function ensureSelectOption(element, value, text) {
        const stringValue = String(value);
        const exists = Array.from(element.options).some(function (option) {
            return String(option.value) === stringValue;
        });

        if (!exists && stringValue !== '') {
            element.appendChild(new Option(text || stringValue, stringValue, true, true));
        }
    }

    function selectOptionTextByValue(options) {
        const result = new Map();

        (options || []).forEach(function (option) {
            result.set(String(option.value), option.text);
        });

        return result;
    }

    function restoreSelect(element, value, savedOptions) {
        const values = normalizeSavedValue(value);
        const optionText = selectOptionTextByValue(savedOptions);

        values.forEach(function (savedValue) {
            ensureSelectOption(element, savedValue, optionText.get(String(savedValue)));
        });

        if (element.multiple) {
            Array.from(element.options).forEach(function (option) {
                option.selected = values.includes(String(option.value));
            });
        } else {
            element.value = values[0] || '';
        }

        triggerFieldChange(element);
    }

    function restoreCheckable(elements, value, type) {
        const values = normalizeSavedValue(value);

        elements.forEach(function (element) {
            const elementValue = String(element.value);
            const isSingleCheckboxFallback = type === 'checkbox'
                && elements.length === 1
                && (values.includes('1') || values.includes('on'));

            element.checked = values.includes(elementValue) || isSingleCheckboxFallback;
            triggerFieldChange(element);
        });
    }

    function restoreTextField(element, value) {
        const restoredValue = Array.isArray(value) ? value[0] : value;
        element.value = restoredValue ?? '';
        triggerFieldChange(element);
    }

    function restoreEditorField(element) {
        if (!window.ArticleEditorJs || typeof window.ArticleEditorJs.setData !== 'function') {
            return;
        }

        const editor = Array.from(document.querySelectorAll('.editorjs-container[data-input-id]')).find(function (item) {
            return item.getAttribute('data-input-id') === element.id;
        });

        if (!editor) {
            return;
        }

        window.ArticleEditorJs.setData(element.id, element.value);
    }

    function restoreForm(form, record) {
        const data = record && record.data ? record.data : null;

        if (!data) {
            return;
        }

        isRestoring = true;

        try {
            Object.entries(data).forEach(function ([name, value]) {
                const elements = Array.from(form.elements).filter(function (element) {
                    return element.name === name && !shouldSkipField(element);
                });

                if (!elements.length) {
                    return;
                }

                const first = elements[0];
                const type = (first.type || '').toLowerCase();

                if (first.tagName === 'SELECT') {
                    const selectOptions = record.selectOptions ? record.selectOptions[name] : [];
                    restoreSelect(first, value, selectOptions);
                    return;
                }

                if (type === 'checkbox' || type === 'radio') {
                    restoreCheckable(elements, value, type);
                    return;
                }

                elements.forEach(function (element) {
                    restoreTextField(element, value);
                    if (type === 'hidden' && element.id) {
                        restoreEditorField(element);
                    }
                });
            });
        } finally {
            isRestoring = false;
        }
    }

    function formatSavedAt(value) {
        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return value || '';
        }

        return date.toLocaleString(config.locale || document.documentElement.lang || undefined);
    }

    function makeButton(text, className) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = text;

        return button;
    }

    function removeNotice(notice) {
        if (notice && notice.parentElement) {
            notice.parentElement.removeChild(notice);
        }
    }

    function showArticleFormNotice(form, storageKey) {
        const record = getRecord(storageKey);

        if (!record || !record.data) {
            return;
        }

        const container = form.closest('[bp-section]') || form.parentElement;
        if (!container || container.querySelector('[data-article-autosave-notice]')) {
            return;
        }

        const notice = document.createElement('div');
        notice.className = 'alert alert-warning d-flex align-items-center gap-2 flex-wrap';
        notice.setAttribute('data-article-autosave-notice', 'true');

        const text = document.createElement('span');
        text.textContent = `${message('form_notice', { date: formatSavedAt(record.updatedAt) })} `;

        const loadButton = makeButton(message('load'), 'btn btn-sm btn-primary');
        loadButton.addEventListener('click', function () {
            restoreForm(form, record);
            removeNotice(notice);
        });

        const deleteButton = makeButton(message('delete'), 'btn btn-sm btn-outline-danger');
        deleteButton.addEventListener('click', function () {
            if (!window.confirm(message('confirm_delete'))) {
                return;
            }

            removeAutosave(storageKey);
            removeNotice(notice);
        });

        notice.appendChild(text);
        notice.appendChild(loadButton);
        notice.appendChild(deleteButton);
        container.insertBefore(notice, container.firstChild);
    }

    function autosaveRecords() {
        const records = [];
        const nextIndex = [];

        getIndex().forEach(function (item) {
            const key = typeof item === 'string' ? item : item && (item.key || item.storageKey);

            if (!key) {
                return;
            }

            const record = getRecord(key);
            if (!record) {
                return;
            }

            if (!record.id) {
                localStorage.removeItem(key);
                return;
            }

            const merged = Object.assign({}, item, record, { key });
            records.push(merged);
            nextIndex.push({
                key,
                id: merged.id,
                type: merged.type,
                mode: merged.mode,
                editUrl: merged.editUrl,
                titleByLocale: merged.titleByLocale,
                updatedAt: merged.updatedAt,
            });
        });

        setIndex(nextIndex);

        return records.sort(function (left, right) {
            return new Date(right.updatedAt).getTime() - new Date(left.updatedAt).getTime();
        });
    }

    function titleForRecord(record) {
        const titles = record.titleByLocale || {};
        const locale = config.locale || document.documentElement.lang;

        if (locale && titles[locale]) {
            return titles[locale];
        }

        const firstTitle = Object.values(titles).find(function (title) {
            return title && String(title).trim() !== '';
        });

        return firstTitle || message('untitled');
    }

    function identifierForRecord(record) {
        if (record.id) {
            return String(record.id);
        }

        return message('new_publication');
    }

    function mainContentContainer() {
        return document.querySelector('main .container-fluid.animated')
            || document.querySelector('main .container-fluid')
            || document.querySelector('main');
    }

    function removeGlobalAutosaveUi() {
        document.querySelectorAll('[data-article-autosave-global], [data-article-autosave-global-container], [data-article-autosave-modal], [data-article-autosave-backdrop]').forEach(function (element) {
            element.remove();
        });
    }

    function closeGlobalModal() {
        removeGlobalAutosaveUi();
        showGlobalAutosaveNotice(false);
    }

    function buildModal(records) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.setAttribute('data-article-autosave-backdrop', 'true');

        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.setAttribute('data-article-autosave-modal', 'true');
        modal.setAttribute('tabindex', '-1');
        modal.style.display = 'block';

        const dialog = document.createElement('div');
        dialog.className = 'modal-dialog modal-lg modal-dialog-scrollable';

        const content = document.createElement('div');
        content.className = 'modal-content';

        const header = document.createElement('div');
        header.className = 'modal-header';

        const title = document.createElement('h5');
        title.className = 'modal-title';
        title.textContent = message('modal_title');

        const closeButton = makeButton('', 'btn-close');
        closeButton.setAttribute('aria-label', message('close'));
        closeButton.addEventListener('click', closeGlobalModal);

        const body = document.createElement('div');
        body.className = 'modal-body';

        const table = document.createElement('table');
        table.className = 'table table-sm table-striped align-middle mb-0';

        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        [message('saved_at'), message('publication'), message('title')].forEach(function (label) {
            const cell = document.createElement('th');
            cell.textContent = label;
            headerRow.appendChild(cell);
        });
        const actionsHeader = document.createElement('th');
        actionsHeader.className = 'text-end';
        actionsHeader.textContent = message('actions');
        headerRow.appendChild(actionsHeader);
        thead.appendChild(headerRow);

        const tbody = document.createElement('tbody');

        records.forEach(function (record) {
            const row = document.createElement('tr');

            const dateCell = document.createElement('td');
            dateCell.textContent = formatSavedAt(record.updatedAt);

            const idCell = document.createElement('td');
            idCell.textContent = identifierForRecord(record);

            const titleCell = document.createElement('td');
            titleCell.textContent = titleForRecord(record);

            const actionsCell = document.createElement('td');
            actionsCell.className = 'text-end text-nowrap';

            if (record.editUrl) {
                const editLink = document.createElement('a');
                editLink.className = 'btn btn-sm btn-primary me-1';
                editLink.href = record.editUrl;
                editLink.textContent = message('edit');
                actionsCell.appendChild(editLink);
            }

            const deleteButton = makeButton(message('delete'), 'btn btn-sm btn-outline-danger');
            deleteButton.addEventListener('click', function () {
                if (!window.confirm(message('confirm_delete'))) {
                    return;
                }

                removeAutosave(record.key);
                row.remove();

                if (!tbody.querySelector('tr')) {
                    removeGlobalAutosaveUi();
                    showGlobalAutosaveNotice(false);
                }
            });
            actionsCell.appendChild(deleteButton);

            row.appendChild(dateCell);
            row.appendChild(idCell);
            row.appendChild(titleCell);
            row.appendChild(actionsCell);
            tbody.appendChild(row);
        });

        table.appendChild(thead);
        table.appendChild(tbody);
        body.appendChild(table);
        header.appendChild(title);
        header.appendChild(closeButton);
        content.appendChild(header);
        content.appendChild(body);
        dialog.appendChild(content);
        modal.appendChild(dialog);

        backdrop.addEventListener('click', closeGlobalModal);
        document.body.appendChild(backdrop);
        document.body.appendChild(modal);
    }

    function showGlobalAutosaveNotice(hasArticleForm) {
        if (hasArticleForm) {
            return;
        }

        const records = autosaveRecords();
        if (!records.length || document.querySelector('[data-article-autosave-global]')) {
            return;
        }

        const container = mainContentContainer();
        if (!container) {
            return;
        }

        const identifiers = records.slice(0, 5).map(identifierForRecord).join(', ');
        const suffix = records.length > 5 ? ', ...' : '';
        const notice = document.createElement('div');
        notice.className = 'alert alert-info d-flex align-items-center gap-2 flex-wrap';
        notice.setAttribute('data-article-autosave-global', 'true');

        const text = document.createElement('span');
        text.textContent = `${message('global_notice', { items: identifiers + suffix })} `;

        const detailsButton = makeButton(message('details'), 'btn btn-sm btn-primary');
        detailsButton.addEventListener('click', function () {
            const currentRecords = autosaveRecords();
            removeGlobalAutosaveUi();

            if (currentRecords.length) {
                buildModal(currentRecords);
            }
        });

        notice.appendChild(text);
        notice.appendChild(detailsButton);

        const pageHeading = document.querySelector('h1[bp-section="page-heading"]');
        const pageHeader = pageHeading && pageHeading.closest('[bp-section="page-header"]');

        if (pageHeader) {
            const noticeContainer = document.createElement('div');
            noticeContainer.className = 'container-fluid d-flex justify-content-end mb-2';
            noticeContainer.setAttribute('data-article-autosave-global-container', 'true');
            notice.classList.add('w-auto', 'mb-0');
            noticeContainer.appendChild(notice);
            pageHeader.before(noticeContainer);
            return;
        }

        container.insertBefore(notice, container.firstChild);
    }

    function initArticleFormAutosave() {
        const form = Array.from(document.querySelectorAll('main form')).find(isArticleForm);

        if (!form) {
            return false;
        }

        const saveFormDebounced = debounce(function () {
            saveForm(form).catch(function (error) {
                console.error('[ArticleAutosave] Could not save form.', error);
            });
        }, 1000);

        const storageKey = ensureAutosaveKey(form, operationForForm(form));
        if (!storageKey) {
            return false;
        }

        showArticleFormNotice(form, storageKey);

        form.addEventListener('input', saveFormDebounced);
        form.addEventListener('change', saveFormDebounced);

        if (window.jQuery) {
            window.jQuery(form).on('select2:select select2:unselect select2:clear', saveFormDebounced);
        }

        return true;
    }

    function boot() {
        applySuccessMarkers();
        const hasArticleForm = initArticleFormAutosave();
        showGlobalAutosaveNotice(Boolean(hasArticleForm));
    }

    window.ArticleAutosave = {
        config,
        keys: {
            prefix: STORAGE_PREFIX,
            index: INDEX_KEY,
        },
        getIndex,
        setIndex,
        getRecord,
        autosaveRecords,
        removeAutosave,
        serializeForm,
        selectedSelectOptions,
        saveForm,
        restoreForm,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
