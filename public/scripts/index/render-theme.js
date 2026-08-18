/**
 * Initialize theme on page load to prevent FOUC
 * Runs immediately in <head> before DOM is ready
 */
(function() {
    const savedTheme = localStorage.getItem('theme');
    const isValidTheme = savedTheme === 'light' || savedTheme === 'dark';
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = isValidTheme ? savedTheme : (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
    
    /**
     * Toggle logo visibility based on current theme
     */
    function toggleLogoVisibility() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const lightLogo = document.querySelector('.header__main-logo[data-dark="light"]');
        const darkLogo = document.querySelector('.header__main-logo[data-theme="dark"]');
        
        if (currentTheme === 'dark') {
            if (darkLogo) darkLogo.style.display = '';
            if (lightLogo) lightLogo.style.display = 'none';
        } else {
            if (lightLogo) lightLogo.style.display = '';
            if (darkLogo) darkLogo.style.display = 'none';
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', toggleLogoVisibility);
    } else {
        setTimeout(toggleLogoVisibility, 0);
    }
})();
      