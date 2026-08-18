@extends('layouts.app')

@section('content')
    <div class="category-title">
        <x-others.breadcrumbs-component :items="$breadcrumbs" />
    </div>

    <div class="two-thirds-container container">
        <div class="two-thirds-container__left">
            <x-containers.article-container-component
                :article="$article"
                :video-embed-url="$videoEmbedUrl"
                :video-thumbnail-url="$videoThumbnailUrl"
                :video-thumbnail-fallback-url="$videoThumbnailFallbackUrl"
                :read-more-url="$readMoreUrl"
                :read-more-title="$readMoreTitle"
            />
        </div>
        <div
            class="two-thirds-container__right"
            data-dynamic-fragment="articles-with-actions"
            data-fragment-url="{{ route('locale.fragments.articles-with-actions', ['locale' => app()->getLocale()]) }}"
        >
            <div class="two-thirds-container__loader" aria-hidden="true"></div>
        </div>
        <x-fragments.dynamic-fragment-loader />
    </div>
@endsection
