function closeDropdown(dropdown) {
    dropdown.classList.remove('header__main-news--open');
    dropdown.querySelector('.header__main-news-trigger')?.setAttribute('aria-expanded', 'false');
}

function toggleDropdown(dropdown) {
    const isOpen = dropdown.classList.toggle('header__main-news--open');
    dropdown.querySelector('.header__main-news-trigger')?.setAttribute('aria-expanded', String(isOpen));
}

function bindDropdown(dropdown) {
    const trigger = dropdown.querySelector('.header__main-news-trigger');

    if (!trigger) {
        return;
    }

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        toggleDropdown(dropdown);
    });
}

function renderDropdown(dropdown, html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();

    const loadedLabel = template.content.querySelector('.header__main-news-trigger span')?.textContent;
    const loadedMenu = template.content.querySelector('.header__main-news-menu');
    const currentLabel = dropdown.querySelector('[data-article-types-current-label]');
    const menuSlot = dropdown.querySelector('[data-article-types-menu]');

    if (loadedLabel && currentLabel) {
        currentLabel.textContent = loadedLabel;
    }

    if (loadedMenu && menuSlot) {
        menuSlot.replaceChildren(loadedMenu);
    }

    dropdown.dataset.articleTypesLoaded = '1';
}

async function loadDropdown(dropdown) {
    const url = dropdown.dataset.url;

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

    renderDropdown(dropdown, await response.text());
    bindDropdown(dropdown);
}

document.addEventListener('DOMContentLoaded', () => {
    const dropdowns = document.querySelectorAll('[data-article-types-dropdown]');

    dropdowns.forEach((dropdown) => {
        loadDropdown(dropdown).catch((error) => {
            console.error('ARTICLE TYPES DROPDOWN ERROR:', error);
        });
    });

    document.addEventListener('click', () => {
        dropdowns.forEach(closeDropdown);
    });
});
