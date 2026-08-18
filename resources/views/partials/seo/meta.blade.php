@if (!empty($seo->title))
    <title>{{ $seo->title }}</title>
    <meta name="title" content="{{ $seo->title }}">
@endif

@if (!empty($seo->description))
    <meta name="description" content="{{ $seo->description }}">
@endif

@if (!empty($seo->keywords))
    <meta name="keywords" content="{{ $seo->keywords }}">
@endif

@if (!empty($seo->newsKeywords))
    <meta name="news_keywords" content="{{ $seo->newsKeywords }}">
@endif

@if (!empty($seo->robots))
    <meta name="robots" content="{{ $seo->robots }}">
@endif

@if (!empty($seo->themeColor))
    <meta name="theme-color" content="{{ $seo->themeColor }}">
@endif

@if (!empty($seo->canonicalUrl))
    <link rel="canonical" href="{{ $seo->canonicalUrl }}">
@endif

@foreach ($seo->alternateUrls as $locale => $url)
    @if (!empty($url))
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
    @endif
@endforeach

@if (!empty($seo->ampUrl))
    <link rel="amphtml" href="{{ $seo->ampUrl }}">
@endif

@if (!empty($seo->ogSiteName))
    <meta property="og:site_name" content="{{ $seo->ogSiteName }}">
@endif

@if (!empty($seo->ogUrl))
    <meta property="og:url" content="{{ $seo->ogUrl }}">
@endif

@if (!empty($seo->ogLocale))
    <meta property="og:locale" content="{{ $seo->ogLocale }}">
@endif

@if (!empty($seo->ogType))
    <meta property="og:type" content="{{ $seo->ogType }}">
@endif

@if (!empty($seo->ogTitle))
    <meta property="og:title" content="{{ $seo->ogTitle }}">
@endif

@if (!empty($seo->ogDescription))
    <meta property="og:description" content="{{ $seo->ogDescription }}">
@endif

@if (!empty($seo->ogImage))
    <meta property="og:image" content="{{ $seo->ogImage }}">
@endif

@if (!empty($seo->ogImageSecureUrl))
    <meta property="og:image:secure_url" content="{{ $seo->ogImageSecureUrl }}">
@endif

@if ($seo->ogImageWidth !== null)
    <meta property="og:image:width" content="{{ $seo->ogImageWidth }}">
@endif

@if ($seo->ogImageHeight !== null)
    <meta property="og:image:height" content="{{ $seo->ogImageHeight }}">
@endif

@if (!empty($seo->articleSection))
    <meta property="article:section" content="{{ $seo->articleSection }}">
@endif

@if (!empty($seo->articlePublishedTime))
    <meta property="article:published_time" content="{{ $seo->articlePublishedTime }}">
@endif

@if (!empty($seo->articleModifiedTime))
    <meta property="og:updated_time" content="{{ $seo->articleModifiedTime }}">
@endif

@if (!empty($seo->articleAuthor))
    <meta property="article:author" content="{{ $seo->articleAuthor }}">
@endif

@foreach ($seo->articleTags as $tag)
    @if (!empty($tag))
        <meta property="article:tag" content="{{ $tag }}">
    @endif
@endforeach

@if (!empty($seo->twitterCard))
    <meta name="twitter:card" content="{{ $seo->twitterCard }}">
@endif

@if (!empty($seo->twitterTitle))
    <meta name="twitter:title" content="{{ $seo->twitterTitle }}">
@endif

@if (!empty($seo->twitterDescription))
    <meta name="twitter:description" content="{{ $seo->twitterDescription }}">
@endif

@if (!empty($seo->twitterSite))
    <meta name="twitter:site" content="{{ $seo->twitterSite }}">
@endif

@if (!empty($seo->twitterImage))
    <meta name="twitter:image" content="{{ $seo->twitterImage }}">
@endif

@if (!empty($seo->twitterCreator))
    <meta name="twitter:creator" content="{{ $seo->twitterCreator }}">
@endif

@if (!empty($seo->author))
    <meta name="author" content="{{ $seo->author }}">
@endif

@if (!empty($seo->copyright))
    <meta name="copyright" content="{{ $seo->copyright }}">
@endif

@if (!empty($seo->publisher))
    <meta name="publisher" content="{{ $seo->publisher }}">
@endif
