async function loadFooterCategories(container) {

    if (!container.classList.contains('footer__categories') || container.dataset.footerCategoriesLoaded) {
        return;
    }

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

    const footerCategories = template.content.querySelector('.footer__categories');

    if (!footerCategories) {
        return;
    }

    footerCategories.dataset.footerCategoriesLoaded = '1';
    container.replaceWith(footerCategories);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-footer-categories]').forEach((container) => {
        loadFooterCategories(container).catch((error) => {
            console.error('FOOTER CATEGORIES ERROR:', error);
        });
    });
});
