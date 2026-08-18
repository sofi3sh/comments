{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item">
    <form method="POST" action="{{ route('set_locale') }}" class="d-inline">
        @csrf
        <select name="locale" onchange="this.form.submit()" class="form-control form-control-sm"
                style="width: auto; display: inline-block; margin-left: 10px; padding: 0.25rem 0.5rem; font-size: 0.875rem;">
            <option value="uk" {{ app()->getLocale() == 'uk' ? 'selected' : '' }}>🇺🇦 Українська</option>
            <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>🇬🇧 English</option>
            <option value="ru" {{ app()->getLocale() == 'ru' ? 'selected' : '' }}>🇷🇺 Русский</option>
        </select>
    </form>
</li>

<li class="nav-item">
    <a class="nav-link" href="{{ route('backpack.dashboard') }}">
        <i class="la la-home nav-icon"></i>
        {{ __('menu.dashboard') }}
    </a>
</li>

@php
    $articleTypesForMenu = app(\App\Services\Article\ArticlePermissionService::class)
        ->accessibleTypesForMenu(backpack_user());
@endphp

@if($articleTypesForMenu->isNotEmpty() && (has_crud_permission('article', 'list') || has_crud_permission('article', 'create')))

    @if(has_crud_permission('article', 'list'))
        <x-backpack::menu-dropdown :open="true" title="{{ __('admin.menu.view_materials') }}" icon="la la-eye">
            <x-backpack::menu-dropdown-item
                :title="__('admin.menu.publication_history')"
                icon="la la-history"
                :link="route('publication-history.index')"
            />

            @foreach($articleTypesForMenu as $articleTypeForMenu)
                <x-backpack::menu-dropdown-item
                    :title="__('admin.menu.view_material_by_type', ['type' => mb_strtolower($articleTypeForMenu->display_name)])"
                    icon="la la-file-alt"
                    :link="route('article.index', ['type' => $articleTypeForMenu->code])"
                />
            @endforeach
        </x-backpack::menu-dropdown>
    @endif

    @if(has_crud_permission('article', 'create'))
        <x-backpack::menu-dropdown :open="true" title="{{ __('admin.menu.create_material') }}" icon="la la-plus-circle">
            @foreach($articleTypesForMenu as $articleTypeForMenu)
                <x-backpack::menu-dropdown-item
                    :title="__('admin.menu.create_material_by_type', ['type' => mb_strtolower($articleTypeForMenu->display_name)])"
                    icon="la la-plus"
                    :link="route('article.create', ['type' => $articleTypeForMenu->code])"
                />

            @endforeach
        </x-backpack::menu-dropdown>
    @endif

{{--    ARTICLES IMPORT OFF  --}}
{{--    <x-backpack::menu-item title="{{ __('article.import.menu_title') }}" icon="la la-upload" :link="route('article.import.form')" />--}}
@endif

@if(has_crud_permission('user', 'list') || has_crud_permission('role', 'list') || has_crud_permission('permission', 'list'))
    @php
        $pendingApplicationsCount = \App\Models\User\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Blogger Candidate', 'Company Representative Candidate']);
        })->count();
    @endphp
    <x-backpack::menu-dropdown title="{{ __('admin.menu.users') }}" icon="la la-users-cog">
        @if(has_crud_permission('user', 'list'))
            <x-backpack::menu-dropdown-item title="{{ __('menu.users') }}" icon="la la-users" :link="route('user.index')" />
        @endif
        @if(has_crud_permission('user-application', 'list'))
            <a class="dropdown-item" href="{{ backpack_url('user-application') }}">
                <i class="la la-id-badge"></i>
                {{ __('admin.menu.user_applications') }}
                @if($pendingApplicationsCount > 0)
                    <span
                        class="ml-1"
                        style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            min-width:18px;
                            height:18px;
                            padding:0 4px;
                            border-radius:999px;
                            font-size:11px;
                            font-weight:600;
                            line-height:1;
                            background-color: var(--tblr-red, #dc3545);
                            color: #fff;
                            margin-left: 4px;
                        "
                    >
                        {{ $pendingApplicationsCount }}
                    </span>
                @endif
            </a>
        @endif
        @if(has_crud_permission('role', 'list'))
            <x-backpack::menu-dropdown-item title="{{ __('menu.roles') }}" icon="la la-user-tag" :link="route('role.index')" />
        @endif
        @if(has_crud_permission('permission', 'list'))
            <x-backpack::menu-dropdown-item title="{{ __('menu.permissions') }}" icon="la la-shield-alt" :link="route('permission.index')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

@if(has_crud_permission('site', 'list'))
    <x-backpack::menu-item title="{{ __('site.admin.title_in_plural') }}" icon="la la-globe" :link="route('site.index')" />
@endif

@if(has_crud_permission('tag', 'list'))
    <x-backpack::menu-item title="{{ __('tag.admin.title_in_plural') }}" icon="la la-tags" :link="route('tag.index')" />
@endif

@if(has_crud_permission('marker', 'list'))
    <x-backpack::menu-item title="{{ __('marker.admin.title_in_plural') }}" icon="la la-bookmark" :link="route('marker.index')" />
@endif

@if(has_crud_permission('category', 'list'))
    <x-backpack::menu-item title="{{ __('category.admin.title_in_plural') }}" icon="la la-sitemap" :link="route('category.index')" />
@endif

@if(has_crud_permission('article-type', 'list'))
    <x-backpack::menu-item title="{{ __('article-type.admin.title_in_plural') }}" icon="la la-tags" :link="route('article-type.index')" />
@endif

@if(has_crud_permission('attachment', 'list'))
    <x-backpack::menu-item title="{{ __('attachment.admin.title_in_plural') }}" icon="la la-images" :link="route('attachment.index')" />
@endif

@if(has_crud_permission('audit', 'show'))
    <x-backpack::menu-item title="{{ __('audit.menu_title') }}" icon="la la-history" :link="route('audit.index')" />
@endif

@if(has_crud_permission('article-field-configuration', 'list') || has_crud_permission('article-translation-permission', 'list') || has_crud_permission('articles-block-settings', 'list'))
    <x-backpack::menu-dropdown title="{{ __('admin.menu.article_settings') }}" icon="la la-cog">
        @if(has_crud_permission('article-field-configuration', 'list'))
            <x-backpack::menu-dropdown-item title="{{ __('article-field-configuration.admin.title_in_plural') }}" icon="la la-list" :link="route('article-field-configuration.index')" />
        @endif
        @if(has_crud_permission('article-translation-permission', 'list'))
            <x-backpack::menu-dropdown-item title="{{ __('article-translation-permission.admin.title_in_plural') }}" icon="la la-shield-alt" :link="route('article-translation-permission.index')" />
        @endif
        @if(has_crud_permission('articles-block-settings', 'create'))
            <x-backpack::menu-dropdown-item title="Вивід статей у блоках" icon="la la-th-large" :link="route('articles-block-settings.create')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

@if(has_crud_permission('locale', 'list'))
    <x-backpack::menu-dropdown title="{{ __('admin.menu.settings') }}" icon="la la-cog">
        <x-backpack::menu-dropdown-item title="{{ __('admin.menu.locales') }}" icon="la la-language" :link="route('locale.index')" />
        @if(has_crud_permission('static-cache', 'delete'))
            <x-backpack::menu-dropdown-item title="Manual static cache" icon="la la-refresh" :link="route('static-cache.manual-public.index')" />
        @endif
{{--        @if(has_crud_permission('settings'))   TODO --}}
        <x-backpack::menu-dropdown-item title="{{__('admin.menu.settings')}}"  icon="la la-cog" :link="backpack_url('settings')" />
{{--        @endif--}}
    </x-backpack::menu-dropdown>
@endif
