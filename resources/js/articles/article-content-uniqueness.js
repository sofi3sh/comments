(function () {
    'use strict';

    function text(root, key, fallback) {
        return root.getAttribute(`data-message-${key}`) || fallback;
    }

    function label(root, key, fallback) {
        return root.getAttribute(`data-label-${key}`) || fallback;
    }

    function setLoading(root, message) {
        const result = root.querySelector('[data-content-uniqueness-result]');
        if (!result) return;

        result.hidden = false;
        result.className = 'article-content-uniqueness-result mt-2 alert alert-info';
        result.textContent = message;
    }

    function render(root, data) {
        const result = root.querySelector('[data-content-uniqueness-result]');
        if (!result) return;

        result.hidden = false;
        result.className = 'article-content-uniqueness-result mt-2 alert alert-secondary';
        result.innerHTML = '';

        if (!data || !data.has_result) {
            result.textContent = text(root, 'empty', 'No uniqueness data yet.');
            result.appendChild(recheckButton(root));
            return;
        }

        const percent = data.uniqueness_percent !== null && data.uniqueness_percent !== undefined
            ? `${data.uniqueness_percent}%`
            : '-';

        const rows = [
            [label(root, 'status', 'Status'), data.status || '-'],
            [label(root, 'uniqueness', 'Uniqueness'), percent],
            [label(root, 'checked-at', 'Checked at'), data.checked_at || '-'],
        ];

        if (data.error_message) {
            rows.push([label(root, 'error', 'Error'), data.error_message]);
        }

        const list = document.createElement('dl');
        list.className = 'row mb-2';

        rows.forEach(([label, value]) => {
            const dt = document.createElement('dt');
            dt.className = 'col-sm-3 mb-1';
            dt.textContent = label;

            const dd = document.createElement('dd');
            dd.className = 'col-sm-9 mb-1';
            dd.textContent = String(value);

            list.appendChild(dt);
            list.appendChild(dd);
        });

        result.appendChild(list);

        if (Array.isArray(data.matches) && data.matches.length) {
            const title = document.createElement('div');
            title.className = 'font-weight-bold mb-1';
            title.textContent = label(root, 'matches', 'Matches');
            result.appendChild(title);

            const matches = document.createElement('ul');
            matches.className = 'mb-2 pl-3';

            data.matches.slice(0, 10).forEach((match) => {
                const item = document.createElement('li');
                const url = match.url || match.link || '';
                const matchPercent = match.percent || match.plagiat || '';
                item.textContent = [url, matchPercent ? `${matchPercent}%` : ''].filter(Boolean).join(' - ');
                matches.appendChild(item);
            });

            result.appendChild(matches);
        }

        result.appendChild(recheckButton(root));
    }

    function recheckButton(root) {
        const wrapper = document.createElement('div');
        wrapper.className = 'mt-2';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-primary btn-sm';
        button.textContent = label(root, 'recheck', 'Send to recheck');
        button.setAttribute('data-content-uniqueness-recheck', 'true');

        wrapper.appendChild(button);
        return wrapper;
    }

    async function load(root) {
        const url = root.getAttribute('data-content-uniqueness-url');
        if (!url) return;

        setLoading(root, text(root, 'loading', 'Loading uniqueness data...'));

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || text(root, 'error', 'Could not load uniqueness data.'));
        }

        render(root, data);
    }

    async function recheck(root) {
        const url = root.getAttribute('data-content-uniqueness-recheck-url');
        const token = root.getAttribute('data-csrf-token');
        if (!url || !token) return;

        setLoading(root, text(root, 'recheck-loading', 'Sending to recheck...'));

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || text(root, 'error', 'Could not send uniqueness recheck.'));
        }

        render(root, data);
    }

    function showError(root, error) {
        const result = root.querySelector('[data-content-uniqueness-result]');
        if (!result) return;

        result.hidden = false;
        result.className = 'article-content-uniqueness-result mt-2 alert alert-danger';
        result.textContent = error.message || text(root, 'error', 'Could not load uniqueness data.');
    }

    function init(root) {
        if (root.getAttribute('data-initialized')) {
            return;
        }

        root.setAttribute('data-initialized', 'true');

        root.addEventListener('click', async function (event) {
            const showButton = event.target.closest('[data-content-uniqueness-show]');
            const recheckButton = event.target.closest('[data-content-uniqueness-recheck]');

            if (!showButton && !recheckButton) {
                return;
            }

            event.preventDefault();

            try {
                if (recheckButton) {
                    await recheck(root);
                } else {
                    await load(root);
                }
            } catch (error) {
                showError(root, error);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.article-content-uniqueness').forEach(init);
    });
})();
