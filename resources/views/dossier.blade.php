
@extends('layouts.app')


@section('content')

    <div class="category-title">
        <x-others.breadcrumbs-component :items="($breadcrumbs ?? [])" />
        <h1 class="category-title__title">{{ __('page.dossier.title') }}</h1>
    </div>

    <x-containers.two-thirds-container-component :type="'dossier'"/>
@endsection