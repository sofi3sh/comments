
@extends('layouts.app')


@section('content')

    <div class="category-title">
        <x-others.breadcrumbs-component :items="($breadcrumbs ?? [])" />
        <h1 class="category-title__title">{{ __('page.significant.title') }} {{ __('page.significant.' . $type) }} {{ __('page.significant.ukraine') }}</h1>
    </div>

    <x-containers.two-thirds-container-component  :type="$type" :articles="$articles" :letter="$letter ?? null"/>
@endsection