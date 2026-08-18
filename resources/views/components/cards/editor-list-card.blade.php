@props(['editor'])

@php
    $name = $editor->fullname ?: $editor->email;
    $position = $editor->position;
    $avatar = $editor->avatar_url ?: asset('images/nofoto.webp');
    $url = url(app()->getLocale() . '/editor/' . $editor->slug . '-' . $editor->id . '.html');
@endphp

<a class="editor-list-card" href="{{ $url }}">
    <span class="editor-list-card__image">
        <img src="{{ $avatar }}" alt="{{ $name }}">
    </span>

    <span class="editor-list-card__content">
        <span class="editor-list-card__name">{{ $name }}</span>

        @if($position)
            <span class="editor-list-card__position">{{ $position }}</span>
        @endif
    </span>
</a>
