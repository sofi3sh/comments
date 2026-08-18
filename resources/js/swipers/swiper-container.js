import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

// Initialize Swiper for swiper-container
document.addEventListener('DOMContentLoaded', () => {
    
    const swiperContainerEl = document.querySelector('.swiper-container__content');
    
    if (swiperContainerEl) { 
        const parentContainer = swiperContainerEl.closest('.swiper-container');
        const nextButton = parentContainer?.querySelector('.swiper-container-button-next');
        const prevButton = parentContainer?.querySelector('.swiper-container-button-prev');
        const pagination = parentContainer?.querySelector('.swiper-container-pagination');
        
        new Swiper(swiperContainerEl, {
            modules: [Navigation, Pagination],
            navigation: {
                nextEl: nextButton,
                prevEl: prevButton,
            },
            pagination: {
                el: pagination,
                clickable: true,
                dynamicBullets: true,
            },
            breakpoints: {
                0: {
                    slidesPerView: 'auto',
                    spaceBetween: 0,
                    centeredSlides: true,
                    centerInsufficientSlides: true,
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 10,
                    centeredSlides: false,
                },
                992: {
                    slidesPerView: 4,
                    spaceBetween: 9,
                },
                1200:{
                    slidesPerView: 4,
                    spaceBetween: 10,
                },
                1536:{
                    slidesPerView: 4,
                    spaceBetween: 16,
                },
                1920:{
                    slidesPerView: 4,
                    spaceBetween: 20,
                }
            },
        });
    }
});
