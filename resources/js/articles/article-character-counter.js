(function () {
    'use strict';

    function outputFor(input) {
        const container = input.closest('.form-group');

        if (!container) {
            return null;
        }

        return container.querySelector('[data-character-counter-output]');
    }

    function stripHtml(value) {
        const template = document.createElement('template');
        template.innerHTML = value;

        return template.content.textContent || '';
    }

    function collectEditorJsText(value, pieces) {
        if (typeof value === 'string') {
            pieces.push(stripHtml(value));
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((item) => collectEditorJsText(item, pieces));
            return;
        }

        if (!value || typeof value !== 'object') {
            return;
        }

        ['text', 'content', 'caption', 'title', 'message'].forEach((key) => {
            if (typeof value[key] === 'string') {
                pieces.push(stripHtml(value[key]));
            }
        });

        ['items', 'content'].forEach((key) => {
            if (Array.isArray(value[key])) {
                collectEditorJsText(value[key], pieces);
            }
        });
    }

    function editorJsText(value) {
        if (!value || value.trim() === '') {
            return '';
        }

        try {
            const data = JSON.parse(value);
            const pieces = [];

            if (Array.isArray(data.blocks)) {
                data.blocks.forEach((block) => collectEditorJsText(block.data, pieces));
            }

            return pieces.join(' ').replace(/\s+/g, ' ').trim();
        } catch (error) {
            return stripHtml(value).replace(/\s+/g, ' ').trim();
        }
    }

    function characterCount(input) {
        if (input.getAttribute('data-character-counter-format') === 'editorjs') {
            return Array.from(editorJsText(input.value)).length;
        }

        return Array.from(input.value).length;
    }

    function update(input) {
        const output = outputFor(input);

        if (!output) {
            return;
        }

        const limit = Number.parseInt(input.getAttribute('data-character-limit') || '0', 10);
        const count = characterCount(input);

        output.textContent = `${count}/${Number.isFinite(limit) ? limit : 0}`;
    }

    function init(root) {
        root.querySelectorAll('[data-character-counter]').forEach((input) => {
            update(input);

            if (input.dataset.characterCounterInitialized === 'true') {
                return;
            }

            input.dataset.characterCounterInitialized = 'true';
            input.addEventListener('input', () => update(input));
            input.addEventListener('change', () => update(input));
        });
    }

    document.addEventListener('DOMContentLoaded', () => init(document));
    document.addEventListener('locale-tab-switched', (event) => init(event.target || document));

    window.ArticleCharacterCounter = {
        init,
        update,
    };
})();
