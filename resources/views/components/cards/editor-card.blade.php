@props(['editor'])

@php
    $name = $editor->fullname ?: $editor->email;
    $position = $editor->position;
    $bio = $editor->bio;
    $avatar = $editor->avatar_url ?: asset('images/nofoto.webp');
@endphp

<section class="editor-card">
    <div class="editor-card__image">
        <img src="{{ $avatar }}" alt="{{ $name }}">
    </div>

    <div class="editor-card__content">
        <div class="editor-card__header">
            <h2 class="editor-card__name">{{ $name }}</h2>

            @if($position)
                <p class="editor-card__position">{{ $position }}</p>
            @endif
        </div>

        @if($bio)
            <div class="editor-card__bio">
                {!! nl2br(e($bio)) !!}
            </div>
        @endif
    </div>
</section>
