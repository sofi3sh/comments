@extends('layouts.app')

@section('content')

    <div class="category-title">
        <h1 class="category-title__title">{{ $author->fullname }}</h1>
    </div>

    <div class="articles-container container">
        <div class="articles-container__right">
            <x-cards.author-card :author="$author" />

            <x-containers.consistent-container-component
                    :articles="$articles"
                    :paginate="$paginate"
            />
        </div>
    </div>

@endsection

