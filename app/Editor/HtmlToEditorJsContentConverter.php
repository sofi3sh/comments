<?php

namespace App\Editor;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class HtmlToEditorJsContentConverter
{
    private const EDITOR_VERSION = '2.31.0';

    /**
     * Block tools configured in resources/js/editorjs.js:
     * paragraph, header, gallery, quote, code, delimiter,
     * warning, embed.
     *
     * Inline tools configured there are not block types and are preserved inside
     * text HTML when possible: link, marker, inlineCode.
     *
     * Not configured as block tools now, so the converter must not emit them:
     * list, table, raw, image/simpleImage.
     *
     * linkTool is configured, but it represents link-preview cards. Plain
     * legacy <a> tags should stay inline inside paragraph/header/quote text.
     */

    /**
     * Конвертирует HTML-контент в JSON-строку формата Editor.js.
     */
    public function convert(?string $html): string
    {
        $blocks = [];
        $html = is_string($html) ? trim($html) : '';

        if ($html !== '') {
            foreach ($this->loadFragment($html) as $node) {
                $blocks = array_merge($blocks, $this->nodeToBlocks($node));
            }
        }

        return json_encode(
            [
                'time'    => (int) floor(microtime(true) * 1000),
                'blocks'  => array_values(array_filter($blocks)),
                'version' => self::EDITOR_VERSION,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Загружает HTML-фрагмент в DOM и возвращает корневые дочерние узлы.
     *
     * @return DOMNode[]
     */
    private function loadFragment(string $html): array
    {
        libxml_use_internal_errors(true);

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="editorjs-converter-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        $root = $document->getElementById('editorjs-converter-root');
        if (!$root) {
            return [];
        }

        return iterator_to_array($root->childNodes);
    }

    /**
     * Преобразует DOM-узел в один или несколько блоков Editor.js.
     *
     * @return array<int, array{type:string,data:array}>
     */
    private function nodeToBlocks(DOMNode $node): array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent ?? '');

            return $text !== '' ? [$this->paragraph($this->escape($text))] : [];
        }

        if (!$node instanceof DOMElement) {
            return [];
        }

        $tag = strtolower($node->tagName);

        $this->removeDescendantsByTag($node, 'picture');
        $this->removeDescendantsByTag($node, 'img');

        $this->removeDescendantsByTag($node, 'iframe');
        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            return [$this->header($node, (int) $matches[1])];
        }

        if ($this->isLegacyWrQuoteElement($node)) {
            return [$this->quote($node)];
        }

        if ($this->isWarningElement($node)) {
            return [$this->warning($node)];
        }

        return match ($tag) {
            'p' => $this->paragraphBlocks($node),
            'blockquote' => [$this->quote($node)],
            'pre' => [$this->code($node)],
            'hr' => [['type' => 'delimiter', 'data' => []]],
            // Legacy images are added anew through GalleryTool instead of being migrated.
            'figure', 'img', 'picture' => [],
            // list is not configured in resources/js/editorjs.js.
            // 'ul', 'ol' => [$this->list($node, $tag === 'ol')],
            'ul', 'ol' => [$this->listFallback($node, $tag === 'ol')],
            // table is not configured in resources/js/editorjs.js.
            // 'table' => [$this->table($node)],
            'iframe' => [],
            'script', 'style' => [],
            default => $this->genericBlocks($node),
        };
    }

    /**
     * Преобразует параграф в текстовый блок.
     *
     * @return array<int, array{type:string,data:array}>
     */
    private function paragraphBlocks(DOMElement $node): array
    {
        $html = trim($this->innerHtml($node));

        return $html !== '' ? [$this->paragraph($html)] : [];
    }

    /**
     * Разбирает неизвестный элемент как набор вложенных блоков или параграф.
     *
     * @return array<int, array{type:string,data:array}>
     */
    private function genericBlocks(DOMElement $node): array
    {
        if ($this->containsBlockElements($node)) {
            $blocks = [];

            foreach ($node->childNodes as $child) {
                $blocks = array_merge($blocks, $this->nodeToBlocks($child));
            }

            return $blocks;
        }

        $html = trim($this->innerHtml($node));

        return $html !== '' ? [$this->paragraph($html)] : [];
    }

    /**
     * Создает блок параграфа Editor.js.
     *
     * @return array{type:string,data:array}
     */
    private function paragraph(string $text): array
    {
        return [
            'type' => 'paragraph',
            'data' => ['text' => $text],
        ];
    }

    /**
     * Создает стандартный блок заголовка Editor.js.
     *
     * @return array{type:string,data:array}
     */
    private function header(DOMElement $node, int $level): array
    {
        return [
            'type' => 'header',
            'data' => [
                'text' => $this->innerHtml($node),
                'level' => $level,
            ],
        ];
    }

    /**
     * Создает блок цитаты Editor.js из blockquote.
     *
     * @return array{type:string,data:array}
     */
    private function quote(DOMElement $node): array
    {
        $caption = '';
        $captionNode = $this->firstElementByTag($node, 'cite') ?: $this->firstElementByTag($node, 'footer');

        if ($captionNode) {
            $caption = $this->text($captionNode);
            $captionNode->parentNode?->removeChild($captionNode);
        }

        return [
            'type' => 'quote',
            'data' => [
                'text' => trim($this->innerHtml($node)),
                'caption' => $caption,
                'alignment' => 'left',
            ],
        ];
    }

    /**
     * Создает warning-блок Editor.js из HTML-блоков с классами warning/alert.
     *
     * @return array{type:string,data:array}
     */
    private function warning(DOMElement $node): array
    {
        $titleNode = $this->firstElementByTag($node, 'strong')
            ?: $this->firstElementByTag($node, 'b')
            ?: $this->firstElementByTag($node, 'h3')
            ?: $this->firstElementByTag($node, 'h4');

        $title = '';
        if ($titleNode) {
            $title = $this->text($titleNode);
            $titleNode->parentNode?->removeChild($titleNode);
        }

        return [
            'type' => 'warning',
            'data' => [
                'title' => $title,
                'message' => trim($this->innerHtml($node)),
            ],
        ];
    }

    /**
     * Создает блок кода Editor.js из pre.
     *
     * @return array{type:string,data:array}
     */
    private function code(DOMElement $node): array
    {
        return [
            'type' => 'code',
            'data' => ['code' => $node->textContent ?? ''],
        ];
    }

    /**
     * Преобразует список в HTML-представление внутри параграфа.
     *
     * @return array{type:string,data:array}
     */
    private function listFallback(DOMElement $node, bool $ordered): array
    {
        $items = [];
        $index = 1;

        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $prefix = $ordered ? $index++ . '. ' : '- ';
            $items[] = $prefix . trim($this->innerHtml($child));
        }

        return $this->paragraph(implode('<br>', array_filter($items)));
    }


    private function isWarningElement(DOMElement $node): bool
    {
        $tag = strtolower($node->tagName);
        if (!in_array($tag, ['aside', 'div', 'section'], true)) {
            return false;
        }

        $class = mb_strtolower($node->getAttribute('class'));

        return str_contains($class, 'warning')
            || str_contains($class, 'alert')
            || str_contains($class, 'notice');
    }

    private function isLegacyWrQuoteElement(DOMElement $node): bool
    {
        $classes = preg_split('/\s+/', trim($node->getAttribute('class'))) ?: [];

        return in_array('wr-quote', $classes, true);
    }

    private function removeDescendantsByTag(DOMElement $node, string $tag): void
    {
        $elements = $node->getElementsByTagName($tag);

        while ($elements->length > 0) {
            $element = $elements->item(0);
            $element?->parentNode?->removeChild($element);
        }
    }

    /**
     * Находит первый вложенный элемент с указанным тегом.
     */
    private function firstElementByTag(DOMElement $node, string $tag): ?DOMElement
    {
        $elements = (new DOMXPath($node->ownerDocument))->query('.//' . $tag, $node);
        $first = $elements?->item(0);

        return $first instanceof DOMElement ? $first : null;
    }

    /**
     * Проверяет, содержит ли элемент дочерние блочные HTML-элементы.
     */
    private function containsBlockElements(DOMElement $node): bool
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->tagName), [
                'address',
                'article',
                'aside',
                'blockquote',
                'div',
                'figure',
                'footer',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'header',
                'hr',
                'iframe',
                'ol',
                'p',
                'pre',
                'section',
                'table',
                'ul',
            ], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает HTML содержимого DOM-узла.
     */
    private function innerHtml(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $this->outerHtml($child);
        }

        return $this->restoreHtmlEntities(trim($html));
    }

    /**
     * Возвращает полный HTML DOM-узла.
     */
    private function outerHtml(DOMNode $node): string
    {
        return $this->restoreHtmlEntities($node->ownerDocument?->saveHTML($node) ?? '');
    }

    /**
     * Возвращает очищенный текст DOM-узла с декодированными HTML-сущностями.
     */
    private function text(DOMNode $node): string
    {
        return trim(html_entity_decode($node->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Экранирует текст для безопасного использования в HTML.
     */
    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function restoreHtmlEntities(string $html): string
    {
        return str_replace("\u{00A0}", '&nbsp;', $html);
    }
}
