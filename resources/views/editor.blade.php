@extends('layouts.app')

@section('content')

    <div class="category-title">
        <h1 class="category-title__title">{{ $editor->fullname }}</h1>
    </div>

    <div class="articles-container container">
        <div class="articles-container__right">
            <x-cards.editor-card :editor="$editor" />

            @if($articles->count())
                <x-containers.consistent-container-component
                        :articles="$articles"
                        :paginate="$paginate"
                />
            @else
                <p class="contributor-articles-empty">{{ __('page.editor.no_articles') }}</p>
            @endif
        </div>
    </div>

@endsection
