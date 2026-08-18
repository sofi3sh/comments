const THEME_STORAGE_KEY = 'theme';
const THEME_LIGHT = 'light';
const THEME_DARK = 'dark';

/**
 * Get current theme from data attribute
 * @returns {string}
 */
function getCurrentTheme() {
    return document.documentElement.getAttribute('data-theme') || THEME_LIGHT;
}

/**
 * Toggle logo visibility based on current theme
 */
function toggleLogoVisibility() {
    const currentTheme = getCurrentTheme();
    const lightLogo = document.querySelector('.header__main-logo[data-dark="light"]');
    const darkLogo = document.querySelector('.header__main-logo[data-theme="dark"]');
    
    if (currentTheme === 'dark') {
        if (darkLogo) darkLogo.style.display = '';
        if (lightLogo) lightLogo.style.display = 'none';
    } else {
        if (lightLogo) lightLogo.style.display = '';
        if (darkLogo) darkLogo.style.display = 'none';
    }

    const images = document.querySelectorAll('.color-theme');
    if (images) {
        images.forEach(image => {
            image.getAttribute('data-theme') === currentTheme ? image.style.display = '' : image.style.display = 'none';
        });
    }
}

/**
 * Set theme and save to localStorage
 * @param {string} theme - Theme value ('light' or 'dark')
 */
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(THEME_STORAGE_KEY, theme);
    toggleLogoVisibility();
}

/**
 * Toggle between light and dark theme
 */
function toggleTheme() {
    const currentTheme = getCurrentTheme();
    const newTheme = currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
    setTheme(newTheme);
}

/**
 * Load saved theme from localStorage or use system preference
 */
function loadSavedTheme() {
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    if (savedTheme && (savedTheme === THEME_LIGHT || savedTheme === THEME_DARK)) {
        setTheme(savedTheme);
    } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        setTheme(prefersDark ? THEME_DARK : THEME_LIGHT);
    }
}

/**
 * Initialize theme system and bind event handlers
 */
function init() {
    loadSavedTheme();

    const themeButton = document.querySelector('.header__actions-theme');
    if (themeButton) {
        themeButton.addEventListener('click', toggleTheme);
    }
}

// Start theme system
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}