@php
    /** @var \App\Models\User\User $entry */
    $actor = backpack_user();
    $isBlocked = method_exists($entry, 'trashed') && $entry->trashed();
    $canManageEntry = $actor
        && (int) $actor->getKey() !== (int) $entry->getKey()
        && has_crud_permission('user', \App\Support\Permissions\CrudOperation::BLOCK)
        && app(\App\Services\User\UserRoleAccessService::class)->canManageTargetUser($entry);
    $route = $isBlocked
        ? route('user.restore', $entry->getKey())
        : route('user.block', $entry->getKey());
    $label = $isBlocked
        ? __('user.actions.unblock')
        : __('user.actions.block');
@endphp

@if($canManageEntry)
    <form method="POST" action="{{ $route }}" class="d-inline-block">
        @csrf
        <button
            type="submit"
            class="btn btn-sm btn-link {{ $isBlocked ? 'text-danger' : 'text-success' }}"
            data-bs-toggle="tooltip"
            title="{{ $label }}"
            aria-label="{{ $label }}"
        >
            <i class="la {{ $isBlocked ? 'la-ban' : 'la-lock' }}"></i>
        </button>
    </form>
@endif
