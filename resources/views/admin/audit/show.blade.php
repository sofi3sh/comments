@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2>
            <span>{{ $title }}</span>
        </h2>
    </section>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <p><strong>{{ __('audit.columns.date') }}:</strong> {{ optional($audit->created_at)->format('d.m.Y H:i:s') }}</p>
            <p><strong>{{ __('audit.columns.user') }}:</strong> {{ $audit->user?->fullname ?: ($audit->user?->email ?? __('audit.system_user')) }}</p>
            <p><strong>{{ __('audit.columns.entity') }}:</strong> <code>{{ $presenter->auditableLabel($audit) }}</code></p>
            <p><strong>{{ __('audit.columns.action') }}:</strong> {{ $presenter->actionLabel($audit) }}</p>
            <p><strong>{{ __('audit.columns.ip') }}:</strong> {{ $audit->ip_address }}</p>
            <p><strong>{{ __('audit.columns.url') }}:</strong> {{ $audit->url }}</p>
            <p><strong>{{ __('audit.columns.user_agent') }}:</strong> {{ $audit->user_agent }}</p>

            <h4 class="mt-4">{{ __('audit.columns.changes') }}</h4>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('audit.columns.field') }}</th>
                            <th>{{ __('audit.columns.old_value') }}</th>
                            <th>{{ __('audit.columns.new_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($presenter->changes($audit) as $change)
                            <tr>
                                <td>{{ $change['label'] }}</td>
                                <td style="white-space: pre-wrap;">{{ $change['old'] ?? __('audit.empty') }}</td>
                                <td style="white-space: pre-wrap;">{{ $change['new'] ?? __('audit.empty') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('audit.no_changes') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
