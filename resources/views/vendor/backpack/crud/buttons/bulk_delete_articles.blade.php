@if (is_backpack_admin() && $crud->getOperationSetting('bulkActions'))
    <a href="javascript:void(0)"
       onclick="bulkDeleteArticles(this)"
       bp-button="bulkDeleteArticles"
       class="btn btn-sm btn-danger bulk-button">
        <i class="la la-trash"></i>
        <span>{{ __('article.bulk_delete.button') }}</span>
    </a>
@endif

@push('after_scripts')
<script>
    if (typeof bulkDeleteArticles !== 'function') {
        function bulkDeleteArticles(button) {
            if (typeof crud.checkedItems === 'undefined' || crud.checkedItems.length === 0) {
                new Noty({
                    type: 'warning',
                    text: '<strong>' + @json(__('article.bulk_delete.no_entries_title')) + '</strong><br>' + @json(__('article.bulk_delete.no_entries_message'))
                }).show();

                return;
            }

            var message = @json(__('article.bulk_delete.confirm')).replace(':count', crud.checkedItems.length);

            swal({
                title: @json(trans('backpack::base.warning')),
                text: message,
                icon: 'warning',
                buttons: {
                    cancel: {
                        text: @json(trans('backpack::crud.cancel')),
                        value: null,
                        visible: true,
                        className: 'bg-secondary',
                        closeModal: true
                    },
                    delete: {
                        text: @json(trans('backpack::crud.delete')),
                        value: true,
                        visible: true,
                        className: 'bg-danger'
                    }
                },
                dangerMode: true
            }).then(function (value) {
                if (! value) {
                    return;
                }

                $.ajax({
                    url: '{{ route('article.bulk-delete', ['type' => request()->route('type')]) }}',
                    type: 'POST',
                    data: {
                        entries: crud.checkedItems,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (result) {
                        new Noty({
                            type: 'success',
                            text: '<strong>' + @json(__('article.bulk_delete.success_title')) + '</strong><br>' + result.message
                        }).show();

                        if (crud.table.rows().count() === crud.checkedItems.length) {
                            crud.table.page('previous');
                        }

                        crud.checkedItems = [];
                        crud.table.draw(false);
                    },
                    error: function (result) {
                        var message = result.responseJSON && result.responseJSON.message
                            ? result.responseJSON.message
                            : @json(__('article.bulk_delete.error_message'));

                        new Noty({
                            type: 'warning',
                            text: '<strong>' + @json(__('article.bulk_delete.error_title')) + '</strong><br>' + message
                        }).show();
                    }
                });
            });
        }
    }
</script>
@endpush
