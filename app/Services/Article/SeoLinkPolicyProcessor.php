<?php

namespace App\Services\Article;

use DOMDocument;
use DOMElement;
use DOMXPath;


final class SeoLinkPolicyProcessor
{
    public function processOld(
        array $content,
        bool $doFollow
    ): array {

        if (! isset($content['blocks'])) {
            return $content;
        }

        foreach ($content['blocks'] as &$block) {

            if (! $this->isParagraphBlock($block)) {
                continue;
            }

            $text = $block['data']['text'] ?? null;

            if (! is_string($text) || $text === '') {
                continue;
            }

            $block['data']['text'] = $this->processLinks(
                $text,
                $doFollow
            );
        }

        unset($block);

        return $content;
    }

    private function processLinksOld(
        string $html,
        bool $doFollow
    ): string {

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new DOMXPath($dom);

        /** @var DOMElement $link */
        foreach ($xpath->query('//a') as $link) {

            $currentRel = $link->getAttribute('rel');

            $updatedRel = $this->updateRelAttribute(
                $currentRel,
                $doFollow
            );

            if ($updatedRel === '') {
                $link->removeAttribute('rel');
                continue;
            }

            $link->setAttribute('rel', $updatedRel);
        }

        libxml_clear_errors();

        return trim($dom->saveHTML());
    }

    private function updateRelAttributeOld(
        string $rel,
        bool $doFollow
    ): string {

        $rels = preg_split(
            '/\s+/',
            mb_strtolower(trim($rel))
        );

        $rels = array_filter($rels);

        $rels = array_unique($rels);

        if ($doFollow) {

            $rels = array_filter(
                $rels,
                static fn (string $value): bool => $value !== 'nofollow'
            );

        } else {

            if (! in_array('nofollow', $rels, true)) {
                $rels[] = 'nofollow';
            }
        }

        return implode(' ', $rels);
    }




    public function process(
        array $content,
        bool $doFollow
    ): array {

        if (! isset($content['blocks'])) {
            return $content;
        }

        foreach ($content['blocks'] as &$block) {

            if (! $this->isParagraphBlock($block)) {
                continue;
            }

            $text = $block['data']['text'] ?? null;

            if (! is_string($text) || $text === '') {
                continue;
            }

            $block['data']['text'] = $this->processLinks(
                $text,
                $doFollow
            );
        }

        unset($block);

        return $content;
    }



    private function isParagraphBlock(array $block): bool
    {
        return ($block['type'] ?? null) === 'paragraph';
    }



    private function processLinks(
        string $html,
        bool $doFollow
    ): string {

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new DOMXPath($dom);

        /** @var DOMElement $link */
        foreach ($xpath->query('//a') as $link) {

            // target blank
            if (! $link->hasAttribute('target')) {
                $link->setAttribute('target', '_blank');
            }

            $currentRel = $link->getAttribute('rel');

            $updatedRel = $this->updateRelAttribute(
                $currentRel,
                $doFollow
            );

            if ($updatedRel === '') {
                $link->removeAttribute('rel');
                continue;
            }

            $link->setAttribute('rel', $updatedRel);
        }

        libxml_clear_errors();

        return trim($dom->saveHTML());
    }


    private function updateRelAttribute(
        string $rel,
        bool $doFollow
    ): string {

        $rels = preg_split(
            '/\s+/',
            mb_strtolower(trim($rel))
        );

        $rels = array_filter($rels);

        $rels = array_unique($rels);

        if ($doFollow) {

            $rels = array_filter(
                $rels,
                static fn (string $value): bool => $value !== 'nofollow'
            );

        } else {

            if (! in_array('nofollow', $rels, true)) {
                $rels[] = 'nofollow';
            }
        }

        return implode(' ', $rels);
    }
}