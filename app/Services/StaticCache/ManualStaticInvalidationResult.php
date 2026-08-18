<?php

namespace App\Services\StaticCache;

class ManualStaticInvalidationResult
{
    /**
     * @param list<string> $paths
     * @param list<string> $failed
     * @param list<string> $blind paths deleted without checking they exist —
     *                            candidates built from the exact patterns, so
     *                            the reported count may overstate reality
     */
    public function __construct(
        public readonly string $type,
        public readonly bool $dryRun,
        public readonly array $paths,
        public readonly array $failed = [],
        public readonly array $blind = [],
    ) {}

    public function count(): int
    {
        return count($this->paths);
    }

    public function blindCount(): int
    {
        return count($this->blind);
    }

    /**
     * @return array{type: string, dry_run: bool, count: int, blind_count: int, paths: list<string>, failed: list<string>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'dry_run' => $this->dryRun,
            'count' => $this->count(),
            'blind_count' => $this->blindCount(),
            'paths' => $this->paths,
            'failed' => $this->failed,
        ];
    }
}
