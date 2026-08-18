<?php

namespace App\Editor;

use BumpCore\EditorPhp\EditorPhp;
use BumpCore\EditorPhp\Blocks\Header;
use BumpCore\EditorPhp\Blocks\Paragraph;
use App\Editor\Blocks\Gallery as CustomGallery;
use App\Editor\Blocks\Quote as CustomQuote;
use App\Editor\Blocks\Paragraph as CustomParagraph;

final class EditorService
{
    public const SAVE_ACTION = 'save';

    public const RENDER_ACTION = 'render';

    /**
     *
     *
     */
    public static function make(string $json, $action = self::SAVE_ACTION): EditorPhp
    {
        EditorPhp::register([
            'gallery' => CustomGallery::class,
            'header' => Header::class,
            'quote' => CustomQuote::class,
            'paragraph' => $action === self::RENDER_ACTION
                ? CustomParagraph::class
                : Paragraph::class
        ]);

        return EditorPhp::make(self::normalizeLegacyBlocks($json));
    }

    private static function normalizeLegacyBlocks(string $json): string
    {
        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['blocks']) || !is_array($data['blocks'])) {
            return $json;
        }

        foreach ($data['blocks'] as &$block) {
            if (!is_array($block) || ($block['type'] ?? null) !== 'highlightedText') {
                continue;
            }

            $block['type'] = 'quote';
            $block['data'] = [
                'text' => $block['data']['text'] ?? '',
                'caption' => $block['data']['caption'] ?? '',
                'alignment' => $block['data']['alignment'] ?? 'left',
            ];
        }
        unset($block);

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $json;
    }
}
