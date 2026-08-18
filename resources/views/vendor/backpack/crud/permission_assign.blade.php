@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        $crud->entity_name_plural => url($crud->route),
        __('permission.assign_to_role') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">{!! $title !!}</h1>
    </section>
@endsection

@section('content')
    <div class="row" bp-section="crud-operation-permission-assign">
        <div class="col-lg-10">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('permission.assign') }}" method="POST" id="permission-assign-form">
                        @csrf

                        <div class="mb-4">
                            <label for="role_id" class="form-label">{{ __('permission.select_role') }}</label>
                            <select name="role_id" id="role_id" class="form-control form-select" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ (string)$r->id === (string)$selectedRoleId ? 'selected' : '' }}>
                                        {{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-hint">{{ __('permission.select_role_hint') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('permission.assign_permissions') }}</label>
                            <div class="border rounded p-3 bg-light">
                                @foreach($permissionsByEntity as $entity => $perms)
                                    <div class="mb-3 pb-3 {{ $loop->last ? '' : 'border-bottom' }}">
                                        <strong class="text-muted d-block mb-2">{{ $entityDisplayNames[$entity] ?? $entity }}</strong>
                                        <div class="row g-2">
                                            @foreach($perms as $permission)
                                                <div class="col-md-6 col-lg-4">
                                                    <label class="form-check">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="form-check-input" {{ in_array($permission->id, $assignedPermissionIds) ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $permission->translated_name }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <span class="la la-save"></span> {{ __('permission.save_assignments') }}
                            </button>
                            <a href="{{ backpack_url('dashboard') }}" class="btn btn-outline-secondary">{{ __('backpack::crud.cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('role_id').addEventListener('change', function() {
            window.location.href = '{{ url($crud->route) }}?role_id=' + this.value;
        });
    </script>
@endsection
