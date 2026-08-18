@extends('layouts.app')

@section('content')

    <div class="category-title">
        <x-others.breadcrumbs-component :items="($breadcrumbs ?? [])" />
        <h1 class="category-title__title">{{ $title }}</h1>
    </div>

    <div class="articles-container container">
        <div class="articles-container__right">
            <x-containers.consistent-container-component
                    :articles="$articles"
                    :paginate="$paginate"
            />
        </div>
    </div>

@endsection
