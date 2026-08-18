@if ($crud->buttons()->where('stack', $stack)->count())
    <div class="d-flex flex-column align-items-center">
        @foreach ($crud->buttons()->where('stack', $stack) as $button)
            {!! $button->getHtml($entry ?? null) !!}
        @endforeach
    </div>
@endif