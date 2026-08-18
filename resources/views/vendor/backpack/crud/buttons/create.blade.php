@if ($crud->hasAccess('create'))
    @php
        $typeId = (int) request()->query('type_id', 0);
        $isArticleCrud = request()->routeIs('article.*')
            && request()->route('type') !== null;

        $reserveUrl = url($crud->route.'/reserve');

        if ($isArticleCrud && $typeId > 0) {
            $reserveUrl .= '?type_id='.$typeId;
        }
    @endphp

    @if ($isArticleCrud)
        <form method="POST" action="{{ $reserveUrl }}" class="d-inline" bp-button="create" data-style="zoom-in">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="la la-plus"></i> <span>{{ trans('backpack::crud.add') }} {{ $crud->entity_name }}</span>
            </button>
        </form>
    @else
        <a href="{{ url($crud->route.'/create') }}" class="btn btn-primary" bp-button="create" data-style="zoom-in">
            <i class="la la-plus"></i> <span>{{ trans('backpack::crud.add') }} {{ $crud->entity_name }}</span>
        </a>
    @endif
@endif
