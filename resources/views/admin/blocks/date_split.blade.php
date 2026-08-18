@if ($entry->published_at)
    <div class="text-muted small">{{ $entry->published_at->translatedFormat('j M Y') }}</div>
    <div class="text-muted small">{{ $entry->published_at->format('H:i') }}</div>
@else
    -
@endif
