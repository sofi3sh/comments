// Language Switcher functionality
document.addEventListener('DOMContentLoaded', () => {
    const languageSwitchers = document.querySelectorAll('[data-language-switcher]');

    languageSwitchers.forEach((switcher) => {
        const button = switcher.querySelector('[data-language-switcher-button]');
        const list = switcher.querySelector('[data-language-switcher-list]');
        const listItems = list.querySelectorAll('.language-switcher__list-item');

        if (!button || !list) {
            return;
        }

        // Toggle dropdown on button click
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            list.classList.toggle('language-switcher__list--open');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!switcher.contains(e.target)) {
                list.classList.remove('language-switcher__list--open');
            }
        });

        listItems.forEach((item) => {
            const link = item.querySelector('.language-switcher__list-link');
            if (link) {
                link.addEventListener('click', (e) => {
                    list.classList.remove('language-switcher__list--open');
                });
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && list.classList.contains('language-switcher__list--open')) {
                list.classList.remove('language-switcher__list--open');
            }
        });
    });
});
