<?php

namespace App\Services\Article;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Str;

class ArticleAnchorProcessor
{
    public function process(?string $html): array
    {
        if ($html === null || $html === '') {
            return [
                'content' => $html,
                'anchors' => [],
            ];
        }

        $anchors = [];
        $usedSlugs = [];

        libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument();

            $dom->loadHTML(
                mb_convert_encoding(
                    '<?xml encoding="UTF-8">' . $html,
                    'HTML-ENTITIES',
                    'UTF-8'
                ),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            $h2Elements = $dom->getElementsByTagName('h2');

            foreach ($h2Elements as $h2) {

                if (!($h2 instanceof DOMElement)) {
                    continue;
                }

                $text = trim($h2->textContent);

                if ($text === '') {
                    continue;
                }

                $slug = $this->makeUniqueSlug($text, $usedSlugs);

                $h2->setAttribute('id', $slug);

                $anchors[] = [
                    'text' => $text,
                    'slug' => $slug,
                ];
            }

            return [
                'content' => $dom->saveHTML(),
                'anchors' => $anchors,
            ];

        } finally {

            libxml_clear_errors();
        }
    }

    protected function makeUniqueSlug(string $text, array &$usedSlugs): string
    {
        $slug = Str::slug(
            html_entity_decode(
                trim($text),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );

        if ($slug === '') {
            $slug = 'anchor';
        }

        $original = $slug;
        $counter = 2;

        while (in_array($slug, $usedSlugs, true)) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        $usedSlugs[] = $slug;

        return $slug;
    }
}