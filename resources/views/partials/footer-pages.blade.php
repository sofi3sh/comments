<nav class="footer__pages">
    <ul class="footer__copyright-links">

        @if(! empty($pages))
            @foreach($pages as $page)
                <li class="footer__copyright-link"><a href="{{ $page['url'] }}">{{ $page['title'] }}</a></li>
            @endforeach
        @else
            <li class="footer__copyright-link"><a href="#">{{__('page.footer.terms_of_use')}}</a></li>
            <li class="footer__copyright-link"><a href="#">{{__('page.footer.privacy_policy')}}</a></li>
            <li class="footer__copyright-link"><a href="#">{{__('page.footer.manage_cookies')}}</a></li>
            <li class="footer__copyright-link"><a href="#">{{__('Accessibility & CC')}}</a></li>
            <li class="footer__copyright-link"><a href="#">{{__('page.footer.about')}}</a></li>
            <li class="footer__copyright-link"><a href="#">{{__('page.footer.newsletters_transcripts')}}</a></li>
        @endif
    </ul>
</nav>
