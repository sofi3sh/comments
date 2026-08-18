<button type="button" class="header__main-news-trigger" aria-expanded="false">
    <span>{{ $currentTypeName }}</span>
    <svg class="header__main-news-icon" width="9" height="5" viewBox="0 0 9 5" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M4.11007 4.56023C3.96275 4.56023 3.81544 4.50548 3.70312 4.39622L0.16863 0.956368C-0.05621 0.737551 -0.05621 0.382776 0.16863 0.164047C0.393379 -0.0546822 0.757846 -0.0546822 0.982704 0.164047L4.11007 3.20782L7.23745 0.164153C7.46229 -0.0545759 7.82672 -0.0545759 8.05145 0.164153C8.2764 0.382882 8.2764 0.737657 8.05145 0.956474L4.51702 4.39633C4.40464 4.50561 4.25734 4.56023 4.11007 4.56023Z"/>
    </svg>
</button>

<ul class="header__main-news-menu">
    @foreach($items as $item)
        <li class="header__main-news-menu-item">
            <a
                class="header__main-news-menu-link @if($item['active']) header__main-news-menu-link--active @endif"
                href="{{ $item['url'] }}"
            >
                {{ $item['name'] }}
            </a>
        </li>
    @endforeach
</ul>
