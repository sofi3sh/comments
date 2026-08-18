<?php

namespace App\Jobs;

use App\Services\StaticCache\ManualArticleStaticInvalidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ManualArticleStaticInvalidationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $type,
    ) {}

    public function handle(ManualArticleStaticInvalidator $invalidator): void
    {
        $result = $invalidator->invalidate($this->type);

//        Log::info('Manual article static invalidation completed', $result->toArray());
    }
}
