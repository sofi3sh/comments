@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2>
            <span>{{ $title }}</span>
        </h2>
    </section>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('audit.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label" for="audit-date-from">{{ __('audit.filters.date_from') }}</label>
                    <input id="audit-date-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="audit-date-to">{{ __('audit.filters.date_to') }}</label>
                    <input id="audit-date-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="audit-user">{{ __('audit.filters.user') }}</label>
                    <select id="audit-user" name="user_id" class="form-control">
                        <option value="">{{ __('audit.all') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>
                                {{ trim($user->fullname) ?: $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="audit-entity">{{ __('audit.filters.entity') }}</label>
                    <select id="audit-entity" name="entity" class="form-control">
                        <option value="">{{ __('audit.all') }}</option>
                        @foreach($entityOptions as $class => $label)
                            <option value="{{ $class }}" @selected(($filters['entity'] ?? '') === $class)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="audit-action">{{ __('audit.filters.action') }}</label>
                    <select id="audit-action" name="action" class="form-control">
                        <option value="">{{ __('audit.all') }}</option>
                        @foreach($actionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label" for="audit-object-id">{{ __('audit.filters.id') }}</label>
                    <input id="audit-object-id" type="number" name="auditable_id" value="{{ $filters['auditable_id'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label" for="audit-ip">{{ __('audit.filters.ip') }}</label>
                    <input id="audit-ip" type="text" name="ip" value="{{ $filters['ip'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-filter"></i> {{ __('audit.filter') }}
                    </button>
                    <a href="{{ route('audit.index') }}" class="btn btn-outline-secondary">
                        <i class="la la-times"></i> {{ __('audit.reset') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('audit.columns.date') }}</th>
                        <th>{{ __('audit.columns.user') }}</th>
                        <th>{{ __('audit.columns.entity') }}</th>
                        <th>{{ __('audit.columns.action') }}</th>
                        <th>{{ __('audit.columns.changes') }}</th>
                        <th>{{ __('audit.columns.ip') }}</th>
                        <th>{{ __('audit.columns.url') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td style="white-space: nowrap;">{{ optional($row['created_at'])->format('d.m.Y H:i:s') }}</td>
                            <td>{{ $row['user'] }}</td>
                            <td><code>{{ $row['entity'] }}</code></td>
                            <td>{{ $row['action'] }}</td>
                            <td>
                                @foreach(array_slice($row['changes'], 0, 4) as $change)
                                    <div>
                                        <strong>{{ $change['label'] }}:</strong>
                                        @if($change['old'] !== null || $change['new'] !== null)
                                            <span>{{ $change['old'] ?? __('audit.empty') }}</span>
                                            <span>→</span>
                                            <span>{{ $change['new'] ?? __('audit.empty') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if(count($row['changes']) > 4)
                                    <small class="text-muted">{{ __('audit.more', ['count' => count($row['changes']) - 4]) }}</small>
                                @endif
                            </td>
                            <td>{{ $row['ip_address'] }}</td>
                            <td style="max-width: 260px; overflow-wrap: anywhere;">{{ $row['url'] }}</td>
                            <td class="text-end">
                                @if($row['show_url'])
                                    <a href="{{ $row['show_url'] }}" class="btn btn-sm btn-link">{{ __('audit.details') }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">{{ __('audit.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $rows->links() }}
        </div>
    </div>
@endsection
