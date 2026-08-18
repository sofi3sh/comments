<label>{{ __('marker.fields.is_system') }}</label>
<div class="form-check mb-0">
    <input
        type="checkbox"
        class="form-check-input"
        id="marker-is-system-information"
        @checked($isSystem)
        disabled
    >
</div>
