@php
    $theme = $theme ?? 'static';
    $socialLinks = settings()->get('social.links') ?? [];

    $hasSocialLinks = collect($socialLinks)->contains(function ($item) {
        return !empty($item['url']) && ($item['enabled'] ?? true);
    });

    $icons = [
        'youtube'   => 'social-links__icon',
        'twitter'   => 'social-links__icon social-links__icon--static',
        'instagram' => 'social-links__icon',
        'telegram'  => 'social-links__icon',
        'tiktok'    => 'social-links__icon',
        'facebook'  => 'social-links__icon',
    ];
@endphp

@if($hasSocialLinks)
<div class="social-links  @if($theme === 'dynamic') social-links--dynamic @else social-links--static @endif">
    @foreach($icons as $name => $class)
        @if(!empty($socialLinks[$name]['url']) && ($socialLinks[$name]['enabled'] ?? true))
            <a class="{{ $class }}" href="{{ $socialLinks[$name]['url'] }}" target="_blank" rel="nofollow noopener noreferrer">
                <x-icon :name="$name" />
            </a>
        @endif
    @endforeach
</div>
@endif
