@if($tags->isNotEmpty())

    <nav @isset($attributes) {{ $attributes->merge(['class' => 'header__main-menu']) }} @else class="header__main-menu" @endisset>
        <ul class="header__main-menu-list">

            @foreach($tags as $tag)
                <li>
                    <a
                        class="header__main-menu-item"
                        href="{{ route('locale.tag.show', [
                            'locale' => app()->getLocale(),
                            'slug' => $tag->slug,
                        ]) }}"
                    >
                        {{ $tag->title }}
                    </a>
                </li>
            @endforeach

        </ul>
    </nav>

@endif
