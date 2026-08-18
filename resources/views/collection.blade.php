@extends('layouts.app')

@section('content')

    <div class="category-title">
        <h1 class="category-title__title">{{ __('admin.articles_block_settings.blocks.' . $code) }}</h1>
    </div>

    <div class="articles-container container">
        <div class="articles-container__right">
            <x-containers.consistent-container-component
                    :articles="$page['articles']"
                    :paginate="$page['paginate']"
            />
        </div>
    </div>

@endsection