@php
    $locale = app()->getLocale();
    $googleNewsUrl = config('services.google_news.comments_url');
    $sourceUrl = trim((string) ($article->source_url ?? ''));
    $tags = $article->relationLoaded('tags') ? $article->tags : collect();
@endphp

<div class="article-footer-meta">
    <div class="article-footer-meta__read-also article-read-also">
        <div id="news-partners" class="read-also"></div>
    </div>

    @if($googleNewsUrl)
        <div class="article-footer-meta__google-news">
            <a href="{{ $googleNewsUrl }}" target="_blank" rel="noopener">
                {{ __('article.footer_meta.google_news') }}
            </a>
        </div>
    @endif

    @if($sourceUrl !== '')
        <div class="article-footer-meta__source">
            <span class="article-footer-meta__label">{{ __('article.footer_meta.source') }}</span>
            <a href="{{ $sourceUrl }}" target="_blank" rel="nofollow noopener">
                {{ $sourceUrl }}
            </a>
        </div>
    @endif

    @if($tags->isNotEmpty())
        <div class="article-footer-meta__tags">
            <span class="article-footer-meta__label">{{ __('article.footer_meta.tags') }}</span>
            <div class="article-footer-meta__tag-list">
                @foreach($tags as $tag)
                    @php
                        $tagTitle = $tag->translate($locale)?->title;
                        $tagSlug = $tag->translate($locale)?->slug;
                    @endphp

                    @if($tagTitle && $tagSlug)
                        <a
                            class="article-footer-meta__tag"
                            href="{{ route('locale.tag.show', ['locale' => $locale, 'slug' => $tagSlug]) }}"
                        >
                            {{ $tagTitle }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
