import Swiper from 'swiper';
import { Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';

// Initialize Swiper for main-swiper
document.addEventListener('DOMContentLoaded', () => {
    const mainSwiperEl = document.querySelector('.main-swiper');
    
    if (mainSwiperEl) {
        const pagination = mainSwiperEl.querySelector('.main-swiper-pagination');
        
        new Swiper(mainSwiperEl, {
            modules: [Pagination],
            slidesPerView: 1,
            spaceBetween: 0,
            centeredSlides: true,
            pagination: {
                el: pagination,
                clickable: true,
                dynamicBullets: true,
            },
        });
    }
});
