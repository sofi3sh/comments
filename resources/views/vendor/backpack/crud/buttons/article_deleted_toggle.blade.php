@php
    $routeParams = ['type' => request()->route('type')];
    $showDeleted = request()->routeIs('article.deleted');

    $activeUrl = route('article.index', $routeParams);
    $url = $showDeleted
        ? $activeUrl
        : route('article.deleted', $routeParams);

    $label = $showDeleted
        ? __('article.deleted.back_to_active')
        : __('article.deleted.show');

    $icon = $showDeleted ? 'la-list' : 'la-trash-restore';
@endphp

<a href="{{ $url }}" class="btn btn-sm btn-secondary">
    <i class="la {{ $icon }}"></i>
    <span>{{ $label }}</span>
</a>
