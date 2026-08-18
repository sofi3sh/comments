@extends('layouts.app')

@section('content')
    <x-containers.main-container-component :page="$page['main']" />
    <x-containers.swiper-container-component :page="$page['swiper']" />
    <x-containers.articles-container-component :page="$page['articles']" />
    <x-containers.latest-container-component :page="$page['latest']" />
    <x-containers.live-container-component :page="$page['live']" />
@endsection
