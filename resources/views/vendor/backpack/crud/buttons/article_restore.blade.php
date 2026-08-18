@if (is_backpack_admin() && $entry->trashed())
    <form method="POST" action="{{ route('article.restore', ['type' => request()->route('type'), 'id' => $entry->getKey()]) }}" class="d-inline-block">
        @csrf
        <button
            type="submit"
            class="btn btn-sm btn-link text-success"
            data-bs-toggle="tooltip"
            title="{{ __('article.deleted.restore') }}"
            aria-label="{{ __('article.deleted.restore') }}"
            onclick="return confirm(@json(__('article.deleted.restore_confirm')));"
        >
            <i class="la la-trash-restore"></i>
        </button>
    </form>
@endif
