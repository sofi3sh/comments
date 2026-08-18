<div class="compact-text-card">
   
   <div class="compact-text-card__date">
      @if($date)
         {{ $date }}
      @endif    
   </div>
  

   <a href="{{ $articleUrl }}" class="compact-text-card__title">
      @if($title)
         {{ $title }}
      @endif
    </a>
</div>