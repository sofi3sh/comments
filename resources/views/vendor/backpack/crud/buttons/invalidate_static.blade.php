<a
    href="javascript:void(0)"
    onclick="invalidateStaticArticle(this)"
    data-route="{{ url($crud->route.'/'.$entry->getKey().'/invalidate-static') }}"
    bp-button="invalidate-static"
    class="btn btn-sm btn-link"
    data-bs-toggle="tooltip"
    title="Invalidate static"
>
    <i class="la la-refresh"></i>
</a>

@push('after_scripts') @if (request()->ajax()) @endpush @endif
@bassetBlock('backpack/crud/buttons/invalidate-static-button.js')
<script>
    if (typeof invalidateStaticArticle !== 'function') {
        function invalidateStaticArticle(button) {
            const route = $(button).attr('data-route');

            swal({
                title: "Invalidate static?",
                text: "Static HTML and rest files for all article translations will be deleted.",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "{!! trans('backpack::crud.cancel') !!}",
                        value: null,
                        visible: true,
                        className: "bg-secondary",
                        closeModal: true,
                    },
                    invalidate: {
                        text: "Invalidate",
                        value: true,
                        visible: true,
                        className: "bg-warning",
                    },
                },
                dangerMode: true,
            }).then((value) => {
                if (!value) {
                    return;
                }

                $.ajax({
                    url: route,
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                    },
                    success: function (result) {
                        new Noty({
                            type: "success",
                            text: result.message || "Static files invalidated",
                        }).show();

                        if (typeof crud !== 'undefined' && typeof crud.table !== 'undefined') {
                            crud.table.draw(false);
                        }
                    },
                    error: function () {
                        swal({
                            title: "Static invalidation failed",
                            text: "Please check the logs and try again.",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                    },
                });
            });
        }
    }
</script>
@endBassetBlock
@if (!request()->ajax()) @endpush @endif
