@if (backpack_theme_config('breadcrumbs') && isset($breadcrumbs) && is_array($breadcrumbs) && count($breadcrumbs))
    @php
        $backpackRoleName = backpack_user()?->roles?->first()?->name;
        $backpackRoleLabel = trans('backpack::crud.admin');

        if (is_string($backpackRoleName) && $backpackRoleName !== '') {
            $backpackRoleKey = 'admin.roles.' . \Illuminate\Support\Str::snake($backpackRoleName);
            $translatedBackpackRole = __($backpackRoleKey);
            $backpackRoleLabel = $translatedBackpackRole === $backpackRoleKey
                ? $backpackRoleName
                : $translatedBackpackRole;
        }
    @endphp

    <nav aria-label="breadcrumb" class="d-none d-lg-block mb-2">
        <ol class="breadcrumb bg-transparent p-0 mx-3 {{ backpack_theme_config('html_direction') == 'rtl' ? 'justify-content-start' : 'justify-content-end' }}">
            @foreach ($breadcrumbs as $label => $link)
                @php
                    $displayLabel = $loop->first ? $backpackRoleLabel : $label;
                @endphp

                @if ($link)
                    <li class="breadcrumb-item text-capitalize"><a href="{{ $link }}">{{ $displayLabel }}</a></li>
                @else
                    <li class="breadcrumb-item text-capitalize active" aria-current="page">{{ $displayLabel }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
