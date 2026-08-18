@extends('layouts.app')


@section('content')

    <div class="category-title">
        <x-others.breadcrumbs-component :items="($breadcrumbs ?? [])" />                           {{--    TODO  --}}
        <h1 class="category-title__title">{{ \Illuminate\Support\Str::ucfirst($category?->name ?? __('page.dossier.title')) }}</h1>
    </div>

    <x-containers.swiper-container-component :page="$page['swiper']" />

    <x-containers.articles-container-component :page="$page['articles']" />

@endsection