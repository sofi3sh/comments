<?php

namespace App\Services\Article;


final readonly class ArticleContentParts
{
    public function __construct(
        public ?string $first = null,
        public ?string $rest = null,
    ) {
    }

    /**
     * @return array{first: string|null, rest: string|null}
     */
    public function toArray(): array
    {
        return [
            'first' => $this->first,
            'rest'  => $this->rest,
        ];
    }
}
