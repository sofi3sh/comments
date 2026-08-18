<?php

namespace App\Editor\Blocks;

use BumpCore\EditorPhp\Blocks\Paragraph as BaseParagraph;

class Paragraph extends BaseParagraph
{
    public function allows(): array|string
    {
        return [
            'text' => [
                'a:href,target,title,rel',
                'abbr:title',
                'b',
                'cite',
                'code',
                'em',
                'i',
                'kbd',
                'q',
                'samp',
                'small',
                'strong',
                'sub',
                'sup',
                'time:datetime',
                'var',
                'u',
                's',
                'del',
                'ins',
                'strike',
                'mark',
            ],
        ];
    }
}