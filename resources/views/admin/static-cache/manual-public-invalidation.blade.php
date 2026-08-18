@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2>
            <span>Manual static cache invalidation</span>
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if(isset($errors) && $errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('static-cache.manual-public.index') }}" class="mb-4">
                        <div class="mb-3">
                            <label for="preview-type" class="form-label">Type</label>
                            <select id="preview-type" name="target" class="form-control">
                                <optgroup label="Public sections">
                                    @foreach($publicTypes as $type)
                                        @php($target = 'public:'.$type)
                                        <option value="{{ $target }}" @selected($selectedTarget === $target)>{{ $type }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Article types">
                                    @foreach($articleTypes as $type)
                                        @php($target = 'article:'.$type)
                                        <option value="{{ $target }}" @selected($selectedTarget === $target)>{{ $type }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <input type="hidden" name="preview" value="1">

                        <button type="submit" class="btn btn-outline-primary">
                            <i class="la la-search"></i> Preview
                        </button>
                    </form>

                    <form method="POST" action="{{ route('static-cache.manual-public.store') }}">
                        @csrf
                        <input id="invalidate-type" type="hidden" name="target" value="{{ $selectedTarget }}">

                        <button
                            type="submit"
                            class="btn btn-warning"
                            onclick="return confirm('Queue manual static invalidation for {{ $selectedTarget }}?')"
                        >
                            <i class="la la-refresh"></i> Queue invalidation
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <strong>Types</strong>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Public sections</strong></li>
                        @foreach($publicTypes as $type)
                            <li><code>public:{{ $type }}</code></li>
                        @endforeach
                        <li class="mt-2"><strong>Article types</strong></li>
                        @foreach($articleTypes as $type)
                            <li><code>article:{{ $type }}</code></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if($preview)
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong>Preview: {{ $preview->type }}</strong>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Matched files: <strong>{{ $preview->count() }}</strong></p>

                        @if($previewCounts !== [])
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            @if($selectedTargetKind === 'article')
                                                <th class="text-end">Public files</th>
                                                <th class="text-end">Private files</th>
                                            @else
                                                <th class="text-end">Files</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($previewCounts as $type => $count)
                                            <tr>
                                                <td><code>{{ $type }}</code></td>
                                                @if($selectedTargetKind === 'article')
                                                    <td class="text-end">{{ $count['public'] ?? 0 }}</td>
                                                    <td class="text-end">{{ $count['private'] ?? 0 }}</td>
                                                @else
                                                    <td class="text-end">{{ $count }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const previewType = document.getElementById('preview-type');
            const invalidateType = document.getElementById('invalidate-type');

            if (!previewType || !invalidateType) {
                return;
            }

            previewType.addEventListener('change', function () {
                invalidateType.value = previewType.value;
            });
        });
    </script>
@endsection
