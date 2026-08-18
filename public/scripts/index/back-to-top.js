const backToTopButton = document.getElementById('backToTopButton');

if (backToTopButton) {
    const toggleVisibility = () => {
        if (window.scrollY > 300) {
            backToTopButton.classList.add('back-to-top--visible');
        } else {
            backToTopButton.classList.remove('back-to-top--visible');
        }
    };

    window.addEventListener('scroll', toggleVisibility, { passive: true });

    backToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    });

    toggleVisibility();
}

