(function () {
    'use strict';

    const fields = ['title', 'excerpt', 'content'];

    function inputId(locale, field) {
        if (field === 'content') {
            return `translations[${locale}][${field}]-input`;
        }

        return `translations[${locale}][${field}]`;
    }

    function input(locale, field) {
        return document.getElementById(inputId(locale, field));
    }

    function getFieldValue(locale, field) {
        const el = input(locale, field);

        return el ? el.value : '';
    }

    function setStatus(root, message, isError) {
        const status = root.querySelector('[data-auto-translate-status]');
        if (!status) return;

        status.textContent = message || '';
        status.classList.toggle('text-danger', Boolean(isError));
        status.classList.toggle('text-muted', !isError);
    }

    async function saveEditors() {
        if (window.ArticleEditorJs && typeof window.ArticleEditorJs.saveAll === 'function') {
            await window.ArticleEditorJs.saveAll();
        }
    }

    async function setFieldValue(locale, field, value) {
        const el = input(locale, field);
        if (!el) return;

        el.value = value || '';

        if (
            field === 'content'
            && window.ArticleEditorJs
            && typeof window.ArticleEditorJs.setData === 'function'
        ) {
            await window.ArticleEditorJs.setData(inputId(locale, field), value || '');
        }

        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function collect(locale) {
        return fields.reduce((result, field) => {
            result[field] = getFieldValue(locale, field);
            return result;
        }, {});
    }

    function hasFilledValue(values) {
        return fields.some((field) => isFilledField(field, values[field]));
    }

    function isFilledField(field, value) {
        if (typeof value !== 'string' || value.trim() === '') {
            return false;
        }

        if (field !== 'content') {
            return true;
        }

        try {
            const data = JSON.parse(value);
            const blocks = Array.isArray(data.blocks) ? data.blocks : [];

            return blocks.some((block) => blockHasText(block));
        } catch (error) {
            return value.trim() !== '';
        }
    }

    function blockHasText(block) {
        if (!block || typeof block !== 'object') {
            return false;
        }

        const type = block.type;
        const data = block.data || {};

        if (['paragraph', 'header'].includes(type)) {
            return hasVisibleText(data.text);
        }

        if (type === 'quote') {
            return hasVisibleText(data.text) || hasVisibleText(data.caption);
        }

        if (type === 'warning') {
            return hasVisibleText(data.title) || hasVisibleText(data.message);
        }

        if (type === 'gallery') {
            return hasVisibleText(data.alt) || hasVisibleText(data.title) || hasVisibleText(data.caption);
        }

        return false;
    }

    function hasVisibleText(value) {
        if (typeof value !== 'string') {
            return false;
        }

        const element = document.createElement('div');
        element.innerHTML = value;

        return element.textContent.trim() !== '';
    }

    function containerFor(root) {
        return root.closest('[data-tab-prefix]');
    }

    function activeSourceLocale(root) {
        const container = containerFor(root);
        const activeButton = container
            ? container.querySelector('[data-locale-tab-button].active')
            : document.querySelector('[data-locale-tab-button].active');

        return activeButton ? activeButton.getAttribute('data-locale-key') : null;
    }

    function updateTargetOptions(root) {
        const sourceLocale = activeSourceLocale(root);
        const targetSelect = root.querySelector('[data-auto-translate-target]');

        if (!sourceLocale || !targetSelect) {
            return;
        }

        let firstAvailable = null;

        Array.from(targetSelect.options).forEach((option) => {
            const isSource = option.value === sourceLocale;

            option.disabled = isSource;
            option.hidden = isSource;

            if (!isSource && firstAvailable === null) {
                firstAvailable = option.value;
            }
        });

        if (targetSelect.value === sourceLocale && firstAvailable !== null) {
            targetSelect.value = firstAvailable;
        }
    }

    function validationMessageFrom(data) {
        if (!data || !data.errors) {
            return data && data.message ? data.message : null;
        }

        const firstKey = Object.keys(data.errors)[0];
        const firstError = firstKey ? data.errors[firstKey][0] : null;

        return firstError || data.message || null;
    }

    async function requestTranslation(root) {
        const url = root.getAttribute('data-auto-translate-url');
        const token = root.getAttribute('data-csrf-token');
        const sourceLocale = activeSourceLocale(root);
        const targetLocale = root.querySelector('[data-auto-translate-target]')?.value;

        if (!url || !sourceLocale || !targetLocale) {
            throw new Error(root.getAttribute('data-message-config-error') || 'Auto translate is not configured.');
        }

        if (sourceLocale === targetLocale) {
            throw new Error(root.getAttribute('data-message-same-locale') || 'Source and target locales must be different.');
        }

        await saveEditors();

        const target = collect(targetLocale);
        const overwrite = hasFilledValue(target)
            ? window.confirm(root.getAttribute('data-message-overwrite-confirm') || 'Overwrite translation?')
            : false;

        if (hasFilledValue(target) && !overwrite) {
            return null;
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                source_locale: sourceLocale,
                target_locale: targetLocale,
                overwrite,
                source: collect(sourceLocale),
                target,
            }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(validationMessageFrom(data) || 'Translation failed.');
        }

        return { data, targetLocale };
    }

    async function applyTranslation(targetLocale, translatedFields) {
        for (const field of fields) {
            if (Object.prototype.hasOwnProperty.call(translatedFields, field)) {
                await setFieldValue(targetLocale, field, translatedFields[field]);
            }
        }
    }

    function init(root) {
        const button = root.querySelector('[data-auto-translate-submit]');
        if (!button || button.getAttribute('data-initialized')) {
            return;
        }

        button.setAttribute('data-initialized', 'true');
        updateTargetOptions(root);

        const container = containerFor(root);
        if (container) {
            container.addEventListener('locale-tab-switched', function () {
                updateTargetOptions(root);
            });
        }

        button.addEventListener('click', async function () {
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = root.getAttribute('data-message-loading') || 'Translating...';
            setStatus(root, '', false);

            try {
                const result = await requestTranslation(root);
                if (result === null) {
                    return;
                }

                const { data, targetLocale } = result;
                await applyTranslation(targetLocale, data.fields || {});

                const skipped = Array.isArray(data.skipped) && data.skipped.length
                    ? ` (${root.getAttribute('data-message-skipped') || 'Skipped'}: ${data.skipped.join(', ')})`
                    : '';

                setStatus(root, (root.getAttribute('data-message-success') || 'Translation applied.') + skipped, false);
            } catch (error) {
                setStatus(root, error.message, true);
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.article-auto-translate').forEach(init);
    });
})();
