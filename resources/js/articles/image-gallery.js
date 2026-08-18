import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-article-image-gallery]').forEach(gallery => {
        const slider = gallery.querySelector('.article-image-gallery__slider');
        new Swiper(slider, {
            modules: [Navigation, Pagination],
            navigation: {
                nextEl: gallery.querySelector('.article-image-gallery__next'),
                prevEl: gallery.querySelector('.article-image-gallery__prev'),
            },
            pagination: {
                el: gallery.querySelector('.article-image-gallery__pagination'),
                clickable: true,
            },
        });
    });
});
