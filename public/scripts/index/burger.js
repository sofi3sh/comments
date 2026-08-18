document.addEventListener('DOMContentLoaded', function() {
    const burgerButton = document.querySelector('.header__main-burger');
    const burgerIconOpen = document.querySelector('.header__main-burger-icon');
    const burgerIconClose = document.querySelector('.header__main-burger-icon-close');
    const burgerContainer = document.querySelector('.burger');
    const burgerOverlay = document.querySelector('.burger__overlay');

    // Opens burger menu
    function openBurger() {
        if (burgerIconOpen) {
            burgerIconOpen.style.display = 'none';
        }
        
        if (burgerIconClose) {
            burgerIconClose.classList.remove('header__main-search-icon-close--hide');
            burgerIconClose.style.display = 'flex';
        }
        
        if (burgerContainer) {
            burgerContainer.classList.remove('burger--hide');
            burgerContainer.classList.add('burger--show');
        }
        
        document.body.style.overflow = 'hidden';
    }

    // Closes burger menu
    function closeBurger() {
        if (burgerIconOpen) {
            burgerIconOpen.style.display = 'flex';
        }
        
        if (burgerIconClose) {
            burgerIconClose.classList.add('header__main-search-icon-close--hide');
            burgerIconClose.style.display = 'none';
        }
        
        if (burgerContainer) {
            burgerContainer.classList.remove('burger--show');
            burgerContainer.classList.add('burger--hide');
        }
        
        document.body.style.overflow = '';
    }

    if (burgerButton && burgerContainer) {
        burgerButton.addEventListener('click', function() {
            const isBurgerOpen = burgerContainer.classList.contains('burger--show');
            
            if (isBurgerOpen) {
                closeBurger();
            } else {
                openBurger();
            }
        });

        if (burgerOverlay) {
            burgerOverlay.addEventListener('click', function() {
                closeBurger();
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && burgerContainer.classList.contains('burger--show')) {
                closeBurger();
            }
        });
    }

    const mainCategoryItems = document.querySelectorAll('.burger__categories-item--main');

    // Toggles subcategories visibility
    function toggleSubCategories(mainItem) {
        const svg = mainItem.querySelector('svg');
        let nextElement = mainItem.nextElementSibling;
        let isOpen = false;

        if (nextElement && nextElement.classList.contains('burger__categories-item--sub')) {
            const currentDisplay = window.getComputedStyle(nextElement).display;
            isOpen = currentDisplay !== 'none';
        }

        nextElement = mainItem.nextElementSibling;
        while (nextElement && nextElement.classList.contains('burger__categories-item--sub')) {
            if (isOpen) {
                nextElement.style.display = 'none';
            } else {
                nextElement.style.display = 'block';
            }
            nextElement = nextElement.nextElementSibling;
        }

        if (svg) {
            if (isOpen) {
                svg.style.transform = 'rotate(0deg)';
            } else {
                svg.style.transform = 'rotate(90deg)';
            }
        }

        if (isOpen) {
            mainItem.classList.remove('burger__categories-item--active');
        } else {
            mainItem.classList.add('burger__categories-item--active');
        }
    }

    mainCategoryItems.forEach(function(mainItem) {
        let nextElement = mainItem.nextElementSibling;
        while (nextElement && nextElement.classList.contains('burger__categories-item--sub')) {
            nextElement.style.display = 'none';
            nextElement = nextElement.nextElementSibling;
        }

        const svg = mainItem.querySelector('svg');
        if (svg) {
            svg.style.transition = 'transform 0.3s ease';
            svg.style.transformOrigin = 'center';
        }

        mainItem.addEventListener('click', function(event) {
            event.preventDefault();
            toggleSubCategories(mainItem);
        });
    });
});
