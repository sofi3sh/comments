@if($hasPages)
    <div class="significant-list__pagination mt-3">
        @if($onFirstPage)
            <span class="significant-list__pagination-button significant-list__pagination-button--disabled">«</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="significant-list__pagination-button">«</a>
        @endif

        @if($startPage > 1)
            <a href="{{ $paginator->url(1) }}" class="significant-list__pagination-button">1</a>
            @if($startPage > 2)
                <span class="significant-list__pagination-button">...</span>
            @endif
        @endif

        @foreach($pageRange as $pageData)
            @if($pageData['isActive'])
                <span class="significant-list__pagination-button significant-list__pagination-button--active">{{ $pageData['page'] }}</span>
            @else
                <a href="{{ $pageData['url'] }}" class="significant-list__pagination-button">{{ $pageData['page'] }}</a>
            @endif
        @endforeach

        @if($endPage < $lastPage)
            @if($endPage < $lastPage - 1)
                <span class="significant-list__pagination-button">...</span>
            @endif
            <a href="{{ $paginator->url($lastPage) }}" class="significant-list__pagination-button">{{ $lastPage }}</a>
        @endif

        @if($hasMorePages)
            <a href="{{ $paginator->nextPageUrl() }}" class="significant-list__pagination-button">»</a>
        @else
            <span class="significant-list__pagination-button significant-list__pagination-button--disabled">»</span>
        @endif
    </div>
@endif
