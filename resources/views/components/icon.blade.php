@props(['name'])

<svg {{ $attributes }}>
    <use href="#icon-{{ $name }}" />
</svg>
