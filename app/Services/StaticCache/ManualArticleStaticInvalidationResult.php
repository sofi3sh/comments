<?php

namespace App\Services\StaticCache;

class ManualArticleStaticInvalidationResult
{
    /**
     * @param list<string> $publicPaths
     * @param list<string> $privatePaths
     * @param list<string> $failedPublic
     * @param list<string> $failedPrivate
     */
    public function __construct(
        public readonly string $type,
        public readonly bool $dryRun,
        public readonly array $publicPaths,
        public readonly array $privatePaths,
        public readonly array $failedPublic = [],
        public readonly array $failedPrivate = [],
    ) {}

    public function publicCount(): int
    {
        return count($this->publicPaths);
    }

    public function privateCount(): int
    {
        return count($this->privatePaths);
    }

    public function count(): int
    {
        return $this->publicCount() + $this->privateCount();
    }

    /**
     * @return array{type: string, dry_run: bool, public_count: int, private_count: int, failed_public: list<string>, failed_private: list<string>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'dry_run' => $this->dryRun,
            'public_count' => $this->publicCount(),
            'private_count' => $this->privateCount(),
            'failed_public' => $this->failedPublic,
            'failed_private' => $this->failedPrivate,
        ];
    }
}
