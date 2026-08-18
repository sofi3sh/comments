<?php

namespace App\Services\Article;

use DOMDocument;

final class SeoLinkPolicyProcessorNew
{
    public function process(
        array $content,
        array $options = []
    ): array
    {

        if (!isset($content['blocks'])) {
            return $content;
        }

        foreach ($content['blocks'] as &$block) {

            if (($block['type'] ?? null) !== 'paragraph') {
                continue;
            }

            $text = $block['data']['text'] ?? null;

            if (!is_string($text) || $text === '') {
                continue;
            }

            $block['data']['text'] = $this->processLinks(
                $text,
                $options
            );
        }

        unset($block);

        return $content;
    }

    private function processLinks(
        string $html,
        array $options
    ): string {

        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        foreach ($dom->getElementsByTagName('a') as $link) {

            $currentRel = $link->getAttribute('rel');

            $updatedRel = $this->updateRelAttribute(
                $currentRel,
                (bool) ($options['do_follow'] ?? false)
            );

            if ($updatedRel === '') {
                $link->removeAttribute('rel');
            } else {
                $link->setAttribute('rel', $updatedRel);
            }

            if (($options['target_blank'] ?? true) === true) {
                $link->setAttribute('target', '_blank');
            }
        }

        $result = $dom->saveHTML();

        libxml_clear_errors();

        return preg_replace(
            '/^<\?xml.+?\?>/i',
            '',
            trim($result)
        );
    }

    private function updateRelAttribute(
        string $rel,
        bool   $doFollow
    ): string
    {

        $rels = preg_split(
            '/\s+/',
            mb_strtolower(trim($rel))
        );

        $rels = array_filter($rels);

        $rels = array_unique($rels);

        if ($doFollow) {

            $rels = array_filter(
                $rels,
                static fn(string $value): bool => $value !== 'nofollow'
            );

        } else {

            if (!in_array('nofollow', $rels, true)) {
                $rels[] = 'nofollow';
            }
        }

//        if (!in_array('noopener', $rels, true)) {
//            $rels[] = 'noopener';
//        }

        return implode(' ', $rels);
    }
}