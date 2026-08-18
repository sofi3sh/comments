@if($entry && $entry->path)
    @if($entry->isImage())
        <div class="form-group">
            @if(isset($showLabel) && $showLabel)
                <label>{{ __('attachment.fields.current_file') }}</label>
            @endif
            <div>
                <img src="{{ $entry->url }}" style="max-width: {{ $maxWidth ?? 300 }}px; max-height: {{ $maxHeight ?? 300 }}px;" class="img-thumbnail" />
                @if(isset($showInfo) && $showInfo)
                    <p class="mt-2"><small>{{ $entry->filename }} ({{ $entry->formatted_size }})</small></p>
                @endif
            </div>
        </div>
    @else
        <div class="form-group">
            @if(isset($showLabel) && $showLabel)
                <label>{{ __('attachment.fields.current_file') }}</label>
            @endif
            <div>
                @if(isset($showInfo) && $showInfo)
                    <p>{{ $entry->filename }} ({{ $entry->formatted_size }})</p>
                @else
                    <i class="la la-file"></i>
                @endif
            </div>
        </div>
    @endif
@else
    @if($entry->isImage())
        <img src="{{ $entry->thumbnail_url }}" style="max-width: {{ $maxWidth ?? 100 }}px; max-height: {{ $maxHeight ?? 100 }}px;" />
    @else
        <i class="la la-file"></i>
    @endif
@endif
