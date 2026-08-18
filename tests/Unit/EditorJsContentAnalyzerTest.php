<?php

namespace Tests\Unit;

use App\Editor\EditorJsContentAnalyzer;
use PHPUnit\Framework\TestCase;

class EditorJsContentAnalyzerTest extends TestCase
{
    /**
     * Перевіряє, що службові значення порожніх блоків не враховуються як текст.
     */
    public function test_empty_blocks_do_not_increase_text_length(): void
    {
        $content = json_encode([
            'blocks' => array_fill(0, 10, [
                'type' => 'paragraph',
                'data' => ['text' => ''],
            ]),
        ], JSON_THROW_ON_ERROR);

        $analyzer = new EditorJsContentAnalyzer();

        self::assertSame('', $analyzer->text($content));
        self::assertSame(0, $analyzer->textLength($content));
    }

    /**
     * Перевіряє, що враховується лише текст підтримуваних типів блоків.
     */
    public function test_text_length_ignores_block_metadata(): void
    {
        $content = json_encode([
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => ['text' => 'Перший <b>текст</b>'],
                ],
                [
                    'type' => 'code',
                    'data' => ['code' => 'echo 1;'],
                ],
                [
                    'type' => 'warning',
                    'data' => ['title' => 'Увага', 'message' => 'Повідомлення'],
                ],
                [
                    'type' => 'gallery',
                    'data' => [
                        'attachment_id' => 10,
                        'url' => 'https://example.com/image.jpg',
                        'title' => 'Метадані зображення',
                        'caption' => 'Підпис зображення',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $analyzer = new EditorJsContentAnalyzer();

        self::assertSame(
            'Перший текст echo 1; Увага Повідомлення',
            $analyzer->text($content),
        );
    }

    public function test_multi_image_gallery_is_meaningful_but_does_not_increase_text_length(): void
    {
        $content = json_encode([
            'blocks' => [[
                'type' => 'gallery',
                'data' => [
                    'images' => [
                        ['attachment_id' => 10],
                        ['attachment_id' => 11],
                    ],
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $analyzer = new EditorJsContentAnalyzer();

        self::assertFalse($analyzer->isEmpty($content));
        self::assertSame('', $analyzer->text($content));
        self::assertSame(0, $analyzer->textLength($content));
    }

    public function test_gallery_with_invalid_images_is_empty(): void
    {
        $content = json_encode([
            'blocks' => [[
                'type' => 'gallery',
                'data' => ['images' => [[], ['attachment_id' => null]]],
            ]],
        ], JSON_THROW_ON_ERROR);

        self::assertTrue((new EditorJsContentAnalyzer())->isEmpty($content));
    }
}
