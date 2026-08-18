function replaceFragment(fragmentEl, content) {
    if (content.trim() === '') {
        return;
    }

    fragmentEl.innerHTML = content;
}

async function fetchFragment(fragmentEl) {
    const response = await fetch(fragmentEl.dataset.fragmentUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (response.status === 304) {
        return null;
    }

    if (!response.ok) {
        throw new Error(await response.text());
    }

    return response.text();
}

async function loadDynamicFragment(fragmentEl) {
    if (!fragmentEl.dataset.fragmentUrl) {
        return;
    }

    try {
        const content = await fetchFragment(fragmentEl);

        if (content !== null) {
            replaceFragment(fragmentEl, content);
        }
    } catch (error) {
        console.error('FRAGMENT FETCH ERROR:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('[data-dynamic-fragment]')
        .forEach(loadDynamicFragment);
});
