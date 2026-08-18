@extends('layouts.app')

@section('content')

    <div class="category-title">
        <x-others.breadcrumbs-component :items="($breadcrumbs ?? [])" />
    </div>

    <x-containers.two-thirds-container-component  :type="'article'" :article="$article" :read-more-url="$readMoreUrl ?? null" :read-more-title="$readMoreTitle ?? null" />


@endsection