<div class="breadcrumbs">
    @foreach($items as $index => $crumb)
        @if(!empty($crumb['url']))
            <a class="breadcrumbs__item" href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
        @else
            <span class="breadcrumbs__item--last">{{ $crumb['label'] }}</span>
        @endif

        @if($index < count($items) - 1)
            <span class="breadcrumbs__separator">/</span>
        @endif
    @endforeach
</div>

