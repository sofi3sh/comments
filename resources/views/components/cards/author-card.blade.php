@props(['author'])

@php
    $name = $author->fullname ?: $author->email;
    $position = $author->position;
    $bio = $author->bio;
    $avatar = $author->avatar_url ?: asset('images/nofoto.webp');
@endphp

<section class="author-card">
    <div class="author-card__image">
        <img src="{{ $avatar }}" alt="{{ $name }}">
    </div>

    <div class="author-card__content">
        <div class="author-card__header">
            <h2 class="author-card__name">{{ $name }}</h2>

            @if($position)
                <p class="author-card__position">{{ $position }}</p>
            @endif
        </div>

        @if($bio)
            <div class="author-card__bio">
                {!! nl2br(e($bio)) !!}
            </div>
        @endif
    </div>
</section>
