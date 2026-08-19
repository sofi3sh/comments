<?php

namespace App\Services\Article;

use App\Editor\EditorService;
use App\Models\Articles\Article;
use Illuminate\Support\Facades\Cache;

class ArticleContentService
{
    private const CACHE_TTL = 300;

    public function __construct(
        private ?ArticleContentSplitPolicy $splitPolicy = null,
    ) {
    }

    public function getRestUrl(string $locale, int $id): string
    {
        $path = restPath($locale, $id);

        return rtrim(config('filesystems.disks.static-private.url'), '/').'/'.$path;
    }

    public function splitContent(Article $article): ArticleContentParts
    {
        $shouldSplit = $this->splitPolicy()->shouldSplitArticle($article);

        $firstKey = articleContentCacheKey($article, $shouldSplit ? 'first' : 'full');
        $restKey  = articleContentCacheKey($article, 'rest');

        $hasFirst = Cache::has($firstKey);
        $hasRest  = $shouldSplit && Cache::has($restKey);

        if ($hasFirst || $hasRest) {

            return new ArticleContentParts(
                Cache::get($firstKey),
                $hasRest ? Cache::get($restKey) : null,
            );
        }

        $parts = $shouldSplit
            ? $this->makeContentParts($article)
            : $this->makeFullContentPart($article);

        Cache::put($firstKey, $parts->first, self::CACHE_TTL);

        if ($shouldSplit) {
            Cache::put($restKey, $parts->rest, self::CACHE_TTL);
        }

        return $parts;
    }

    private function splitPolicy(): ArticleContentSplitPolicy
    {
        return $this->splitPolicy ??= new ArticleContentSplitPolicy();
    }

    private function makeContentParts(Article $article): ArticleContentParts
    {
        $contentHtml = $article->content_html;
        $contentJson = $article->content;

        if (!$this->isBlank($contentHtml)) {
            return $this->splitContentHtml($contentHtml);
        }

        if (!$this->isBlank($contentJson)) {
            return $this->splitContentJson($contentJson);
        }

        return new ArticleContentParts();
    }


    private function makeFullContentPart(Article $article): ArticleContentParts
    {
        return new ArticleContentParts($this->getContentHtml($article));
    }

    public function splitContentHtml(string $contentHtml): ArticleContentParts
    {
        $html = trim($contentHtml);

        if ($html === '') {
            return new ArticleContentParts();
        }

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $first = null;
        $rest = '';
        $foundFirst = false;

        foreach ($dom->childNodes as $node) {

            if (!$foundFirst) {
                if (
                    $node instanceof \DOMElement
                    && strtolower($node->tagName) === 'p'
                    && trim(html_entity_decode($node->textContent)) !== ''
                ) {
                    $first = $dom->saveHTML($node);
                    $foundFirst = true;
                }

                continue;
            }

            $rest .= $dom->saveHTML($node);
        }

        if (!$first) {
            return new ArticleContentParts($html);
        }

        $rest = trim($rest);

        return new ArticleContentParts(
            $first,
            $rest !== '' ? $rest : null
        );
    }


    public function splitContentJson(string|null $content): ArticleContentParts
    {
        if (empty($content)) {
            return new ArticleContentParts();
        }

        // если пришёл JSON string
        if (is_string($content)) {

            $decoded = json_decode($content, true);

            if (!is_array($decoded)) {
                return new ArticleContentParts();
            }

            $content = $decoded;
        }

        $blocks = $content['blocks'] ?? [];

        if (empty($blocks)) {
            return new ArticleContentParts();
        }

        $firstBlock = null;

        $restBlocks = [];

        $foundFirst = false;

        foreach ($blocks as $block) {

            $type = $block['type'] ?? null;

            $text = trim(
                strip_tags($block['data']['text'] ?? '')
            );

            // первый НЕ пустой paragraph
            if (
                !$foundFirst
                && $type === 'paragraph'
                && $text !== ''
            ) {

                $firstBlock = $block;

                $foundFirst = true;

                continue;
            }

            // всё остальное
            if ($foundFirst) {
                $restBlocks[] = $block;
            }
        }

        // если paragraph не найден
        if (!$firstBlock) {

            $full = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return new ArticleContentParts(
                $full ? EditorService::make($full, EditorService::RENDER_ACTION)->toHtml() : null,
                null
            );
        }

        $base = [
            'time' => $content['time'] ?? now()->timestamp,
            'version' => $content['version'] ?? null,
        ];


        $first = array_merge($base, [
            'blocks' => [$firstBlock],
        ]);

        $first = json_encode($first);

        $rest = !empty($restBlocks)
            ? array_merge($base, [
                'blocks' => $restBlocks,
            ])
            : null;

        $rest = $rest
            ? json_encode($rest)
            : null;

        $firstHtml = EditorService::make($first, EditorService::RENDER_ACTION)->toHtml();

        $restHtml = $rest !== null
            ? EditorService::make($rest, EditorService::RENDER_ACTION)->toHtml()
            : null;

        return new ArticleContentParts($firstHtml, $restHtml);
    }


    private function isBlank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }


    /**
     * For special cases
     *  content JSON  ->  FIRST !!!
     *
     * @param Article $article
     * @return mixed|string|null
     */
    public function getContentHtml(Article $article)
    {
        $contentHtml = $article->content_html;
        $contentJson = $article->content;

        if (!$this->isBlank($contentJson)) {
            $rendered = EditorService::make($contentJson, EditorService::RENDER_ACTION)->toHtml();

            if (!$this->isBlank($rendered)) {
                return $rendered;
            }
        }

        if (!$this->isBlank($contentHtml)) {
            return $contentHtml;
        }

        return null;
    }
}
