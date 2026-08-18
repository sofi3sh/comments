@php
    /** @var \App\Models\User\User $entry */
@endphp

<form method="POST" action="{{ route('user-application.approve', $entry->getKey()) }}" class="d-inline-block">
    @csrf
    <button
            type="submit"
            class="btn btn-sm btn-link text-success"
            data-bs-toggle="tooltip"
            title="{{ __('user-application.actions.approve') }}"
            aria-label="{{ __('user-application.actions.approve') }}"
    >
        <i class="la la-check"></i>
    </button>
</form>

<form method="POST" action="{{ route('user-application.reject', $entry->getKey()) }}" class="d-inline-block">
    @csrf
    <button
            type="submit"
            class="btn btn-sm btn-link text-danger"
            data-bs-toggle="tooltip"
            title="{{ __('user-application.actions.reject') }}"
            aria-label="{{ __('user-application.actions.reject') }}"
    >
        <i class="la la-times"></i>
    </button>
</form>
