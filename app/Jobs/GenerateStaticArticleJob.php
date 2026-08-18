<?php

namespace App\Jobs;

use App\Services\Article\StaticFileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateStaticArticleJob implements ShouldQueue
{
    use
        Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Writes go to S3, so a failure can be a transient network error rather
     * than a bug. Retrying is safe: generate() overwrites unconditionally and
     * releases the lock in its own finally.
     */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30];

    protected string $uri;
    protected object $articleData;
    protected string $lockKey;


    /**
     * Create a new job instance.
     */
    public function __construct(
        string $uri,
        object $articleData,
        string $lockKey,
    )
    {
        $this->uri = $uri;
        $this->articleData = $articleData;
        $this->lockKey = $lockKey;
    }

    /**
     * Execute the job.
     */
    public function handle(StaticFileService $service): void
    {
        app(\App\SEO\SchemaGraph::class)->reset();

        $service->generate($this->uri, $this->articleData, $this->lockKey);
    }
}
