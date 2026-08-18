<?php

namespace App\Editor\Blocks;

use BumpCore\EditorPhp\Blocks\Quote as BaseQuote;

final class Quote extends BaseQuote
{
    public function allows(): array|string
    {
        return [
            'text' => [
                'a:href,target,title,rel',
                'abbr:title',
                'b',
                'br',
                'cite',
                'code',
                'del',
                'em',
                'i',
                'ins',
                'kbd',
                'mark',
                'q',
                's',
                'samp',
                'small',
                'strike',
                'strong',
                'sub',
                'sup',
                'time:datetime',
                'u',
                'var',
            ],
            'caption' => [],
            'alignment' => [],
        ];
    }

    public function render(): string
    {
        $text = trim((string) $this->data->get('text', ''));
        $caption = trim((string) $this->data->get('caption', ''));
        $alignment = $this->data->get('alignment', 'left') === 'center' ? ' article-quote--center' : '';
        $captionHtml = $caption !== ''
            ? '<cite class="article-quote__caption">' . htmlspecialchars($caption, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</cite>'
            : '';

        return '<blockquote class="article-quote' . $alignment . '"><p class="text-base [&>a]:text-blue-600 [&>a]:underline hover:[&>a]:text-blue-400 [&>code]:text-red-500 [&>code]:bg-red-100 [&>code]:px-1 [&>code]:rounded-md [&>code]:whitespace-nowrap [&>code]:font-medium [&>mark]:bg-yellow-200">' . $text . '</p>' . $captionHtml . '</blockquote>';
    }
}