<div class="d-inline-flex align-items-center">
    <label class="form-switch switch switch-sm switch-label switch-pill mb-0 breaking-news-switch">
        <input type="hidden" name="breaking_news" value="{{ $enabled ? '1' : '0' }}" data-breaking-news-input>
        <input
            type="checkbox"
            class="switch-input form-check-input"
            id="breaking-news-switch"
            data-breaking-news-toggle
            data-marker-id="{{ $markerId }}"
            @checked($enabled)
        >
        <span class="switch-slider"></span>
    </label>
    <label class="font-weight-normal mb-0 ml-2" for="breaking-news-switch">
        {{ __('article.fields.breaking_news') }}
    </label>
</div>

<script>
    (function () {
        // Знаходить поле вибору маркерів у формі Backpack.
        function markerSelect() {
            return document.querySelector('select[name="markers[]"], select[name="markers"]');
        }

        // Синхронізує маркер «Срочна новина» зі станом перемикача.
        function syncMarker(select, markerId, enabled) {
            if (!select) {
                return;
            }

            Array.from(select.options).forEach(function (option) {
                if (option.value === String(markerId)) {
                    option.selected = enabled;
                }
            });

            if (window.jQuery) {
                window.jQuery(select).trigger('change');
            } else {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Ініціалізує перемикач після завантаження форми.
        function init() {
            const toggle = document.querySelector('[data-breaking-news-toggle]');
            const input = document.querySelector('[data-breaking-news-input]');

            if (!toggle || !input) {
                return;
            }

            const markerId = toggle.getAttribute('data-marker-id');

            toggle.addEventListener('change', function () {
                input.value = toggle.checked ? '1' : '0';
                syncMarker(markerSelect(), markerId, toggle.checked);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
