<?php

namespace App\Jobs;

use App\Services\Article\StaticFileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class GenerateStaticPublicFileJob implements ShouldQueue
{
    use
        Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Writes go to S3, so a failure can be a transient network error rather
     * than a bug. Retrying is safe — storePublic() always overwrites.
     */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $host,
        protected string $path,
        protected string $content,
        protected string $lockKey,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(StaticFileService $service): void
    {
        try {
            $service->storePublic($this->host, $this->path, $this->content);
        } finally {
            Redis::del($this->lockKey);
        }
    }
}
