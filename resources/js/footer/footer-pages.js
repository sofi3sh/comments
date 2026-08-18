async function loadFooterPages(container) {
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

    const footerPages = template.content.querySelector('.footer__pages');

    if (!footerPages) {
        return;
    }

    footerPages.dataset.footerPagesLoaded = '1';
    container.replaceWith(footerPages);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-footer-pages]').forEach((container) => {
        loadFooterPages(container).catch((error) => {
            console.error('FOOTER PAGES ERROR:', error);
        });
    });
});
