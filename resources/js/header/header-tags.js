async function loadHeaderTags(container) {
    const url = container.dataset.url;

    if (!url) {
        return;
    }

    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(await response.text());
    }

    const template = document.createElement('template');
    template.innerHTML = (await response.text()).trim();

    const headerTags = template.content.querySelector('.header__main-menu');

    if (!headerTags) {
        return;
    }

    headerTags.dataset.headerTagsLoaded = '1';
    container.replaceWith(headerTags);
}

function initHeaderTags() {
    document.querySelectorAll('[data-header-tags]').forEach((container) => {
        loadHeaderTags(container).catch((error) => {
            console.error('HEADER TAGS ERROR:', error);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeaderTags);
} else {
    initHeaderTags();
}
