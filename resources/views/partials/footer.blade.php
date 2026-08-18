<footer>
    <div class="footer__container">
        <div
            class="footer__categories"
            data-footer-categories
            data-url="{{ route('locale.footer.categories', ['locale' => app()->getLocale()]) }}"
        ></div>
        
        <div class="footer__actions">
            <div class="footer__actions-logo">
                <x-logo-component :theme="'static'"/>
            </div>
            <div class="footer__actions-social">
                <span>{{ __('page.footer.follow_us') }}</span>
                <div class="footer__actions-social-links">
                    <x-social-links-component />
                </div>
            </div>
        </div>

        <div class="footer__description">
            <p class="footer__description-text">{{ settings('settings')->get('footer.text_1') }}</p>
            <p class="footer__description-text">{{settings('settings')->get('footer.text_2')}}</p>
        </div>

        <div class="footer__copyright">

            <div
                class="footer__copyright-pages"
                data-footer-pages
                data-url="{{ route('locale.footer.pages', ['locale' => app()->getLocale()]) }}"
            ></div>

            <span class="footer__copyright-text">© «Комментарии», 2005 – 2025</span>
        </div>
    </div>
    

</footer>
