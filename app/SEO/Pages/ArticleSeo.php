<?php

namespace App\SEO\Pages;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Translate\ArticleTranslation;
use App\SEO\Data\SeoPage;
use App\Services\Article\ArticleContentTextExtractor;
use App\Services\Article\YouTubeVideoResolver;
use Illuminate\Support\Str;

final class ArticleSeo extends AbstractSeo
{
    public function __construct(private readonly Article $article)
    {
    }

    public static function make(Article $article): self
    {
        return new self($article);
    }

    public function toSeoPage(): SeoPage
    {
        $article = $this->article;
        $locale = app()->getLocale();
        $translation = $article->translate($locale);
        $seoTranslation = $article->seoMeta?->translate($locale);
        $image = $this->resolveImage($article);

        return new SeoPage(
            title: $this->resolveTitle($article, $seoTranslation?->meta_title),
            description: $this->resolveDescription($translation, $seoTranslation?->meta_description),
            keywords: $this->resolveKeywords($article, $seoTranslation?->meta_keywords),
            canonicalUrl: $article->getArticleUrl(),
            alternateUrls: $this->resolveAlternateUrls($article),
            ogType: 'article',
            imageUrl: $image['url'] ?? null,
            imageWidth: $image['width'] ?? null,
            imageHeight: $image['height'] ?? null,
            ampUrl: null, // TODO: add AMP route/source if AMP pages appear in the project.
            articleSection: $this->resolveArticleSection($article),
            articlePublishedTime: $article->published_at?->toAtomString(),
            articleModifiedTime: $article->updated_at?->toAtomString(),
            articleAuthor: $this->resolveArticleAuthor($article),
            articleTags: $this->resolveArticleTags($article),
        );
    }

    private function resolveTitle(Article $article, ?string $seoTitle): ?string
    {
        return $this->firstNonEmpty([
            $seoTitle,
            $article->title_with_markers,
            $article->title,
        ]);
    }

    private function resolveDescription(?ArticleTranslation $translation, ?string $seoDescription): ?string
    {
        $contentText = $translation !== null
            ? app(ArticleContentTextExtractor::class)->extract($translation)
            : null;

        return $this->firstNonEmpty([
            $seoDescription,
            $translation?->excerpt,
            $contentText !== null ? Str::limit($contentText, 300, '') : null,
        ]);
    }

    private function resolveKeywords(Article $article, ?string $seoKeywords): ?string
    {
        if ($this->filled($seoKeywords)) {
            return trim((string) $seoKeywords);
        }

        $tags = $this->resolveArticleTags($article);

        return $tags !== [] ? implode(', ', $tags) : null;
    }

    /**
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    private function resolveImage(Article $article): ?array
    {
        // todo: add dedicated Facebook

        if ($article->type?->code === ArticleType::VIDEO) {
            $youtubeId = $article->meta()
                ->where('field', 'youtube_id')
                ->whereNull('locale')
                ->value('value');
            $thumbnailUrl = YouTubeVideoResolver::thumbnailUrl($youtubeId);

            return $thumbnailUrl ? ['url' => $thumbnailUrl] : null;
        }

        $cover = $article->thumbnailAttachment()->first();

        if ($cover && $cover->isImage()) {
            $metadata = $cover->getSizeMetadata('cover');

            return [
                'url' => $cover->getSizeUrl('cover'),
                'width' => $metadata['width'] ?? null,
                'height' => $metadata['height'] ?? null,
            ];
        }

        return null;
    }

    private function resolveAlternateUrls(Article $article): array
    {
        $alternateUrls = [];

        foreach ($article->getAvailableLocales() as $locale) {
            $code = $locale->code ?? null;

            if (! is_string($code) || $code === '') {
                continue;
            }

            $alternateUrls[$code] = $article->getArticleUrlForLocale($code);
        }

        return $alternateUrls;
    }

    private function resolveArticleSection(Article $article): ?string
    {
        return $this->firstNonEmpty([
            $article->category?->name,
            $article->type?->name,
        ]);
    }

    private function resolveArticleTags(Article $article): array
    {
        $tags = $article->relationLoaded('tags')
            ? $article->tags
            : $article->tags()->with('translations')->get();

        return $tags
            ->map(fn ($tag) => is_string($tag->title) ? trim($tag->title) : '')
            ->filter(fn (string $tag) => $tag !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveArticleAuthor(Article $article): ?string
    {
        return $article->authors()->first()?->fullName;
    }
}
