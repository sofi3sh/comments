@extends('layouts.app')

@section('content')

    <div class="category-title">
        <h1 class="category-title__title">{{ __('page.editors.title') }}</h1>
    </div>

    <div class="container">
        <div class="editors-list">
            @if($editors->count())
                <div class="editors-list__grid">
                    @foreach ($editors as $editor)
                        <x-cards.editor-list-card :editor="$editor" />
                    @endforeach
                </div>

                <x-others.pagination-component :paginator="$editors" />
            @else
                <p class="editors-list__empty">{{ __('page.editors.empty') }}</p>
            @endif
        </div>
    </div>

@endsection
