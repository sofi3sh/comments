@extends('layouts.app')

@section('content')
    <section class="page">
        <div class="container">
            <x-others.breadcrumbs-component :items="($breadcrumbs ?? [])" />

            @if(!empty($title))
                <h1 class="page__title">{{ $title }}</h1>
            @endif

            @if(!empty($content))
                <div class="page__content article-container__content">
                    {!! $content !!}
                </div>
            @endif
        </div>
    </section>
@endsection

