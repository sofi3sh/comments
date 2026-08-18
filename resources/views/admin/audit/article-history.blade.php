<div class="card mb-0">
    <div class="card-header">
        <strong>{{ __('audit.history_title') }}</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('audit.columns.date') }}</th>
                    <th>{{ __('audit.columns.user') }}</th>
                    <th>{{ __('audit.columns.action') }}</th>
                    <th>{{ __('audit.columns.changes') }}</th>
                    <th>{{ __('audit.columns.ip') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td style="white-space: nowrap;">{{ optional($row['created_at'])->format('d.m.Y H:i:s') }}</td>
                        <td>{{ $row['user'] }}</td>
                        <td>{{ $row['action'] }}</td>
                        <td>
                            @foreach(array_slice($row['changes'], 0, 3) as $change)
                                <div>
                                    <strong>{{ $change['label'] }}:</strong>
                                    @if($change['old'] !== null || $change['new'] !== null)
                                        <span>{{ $change['old'] ?? __('audit.empty') }}</span>
                                        <span>→</span>
                                        <span>{{ $change['new'] ?? __('audit.empty') }}</span>
                                    @endif
                                </div>
                            @endforeach
                            @if(count($row['changes']) > 3)
                                <small class="text-muted">{{ __('audit.more', ['count' => count($row['changes']) - 3]) }}</small>
                            @endif
                        </td>
                        <td>{{ $row['ip_address'] }}</td>
                        <td class="text-end">
                            @if($row['show_url'])
                                <a href="{{ $row['show_url'] }}" class="btn btn-sm btn-link" target="_blank">{{ __('audit.details') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('audit.no_history') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
