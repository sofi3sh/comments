<a class="logo" href="{{ route('homepage') }}">
    @if($theme === 'static')
        <img src="{{ asset('images/logo-light.png') }}" alt="Logo">  
    @else
        <img class="color-theme" data-theme="light" src="{{ asset('images/logo.png') }}" alt="Logo">
        <img class="color-theme" data-theme="dark" src="{{ asset('images/logo-light.png') }}" alt="Logo">
    @endif
</a>