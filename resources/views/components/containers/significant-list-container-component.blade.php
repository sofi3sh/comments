<div class="significant-list">
   <div class="significant-list__navigation">
     @foreach($alphabet as $letter)
         @php
             $isActive = $currentLetter === $letter;
             $url = route('locale.significant', ['locale'=> app()->getLocale(), 'type' => $type, 'letter' => $letter]);
         @endphp
         @if($isActive)
             <span class="significant-list__navigation-letter significant-list__navigation-letter--active">{{ $letter }}</span>
         @else
             <a href="{{ $url }}" class="significant-list__navigation-letter" data-letter="{{ $letter }}">
             {{ $letter }}
         </a>
         @endif
     @endforeach
   </div>
   <div class="significant-list__list">
       @foreach($articles as $article)
           <x-cards.significant-card-component :article="$article" />
       @endforeach
   </div>

    @if($paginate)
        <x-others.pagination-component :paginator="$articles" />
    @endif
</div>