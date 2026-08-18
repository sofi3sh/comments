<?php

namespace App\Console\Commands;

use App\Models\Articles\ArticleContent;
use App\Services\Article\ArticleContentUniquenessService;
use Illuminate\Console\Command;

class ProcessArticleContentUniqueness extends Command
{
    protected $signature = 'app:process-article-content-uniqueness {--limit= : Maximum records to process}';

    protected $description = 'Process pending article content uniqueness checks';

    public function handle(ArticleContentUniquenessService $service): int
    {
        $limit = (int) ($this->option('limit') ?: config('article_content.batch_size', 5));
        $processed = 0;

        ArticleContent::query()
            ->where('provider', ArticleContent::PROVIDER_CONTENT_WATCH)
            ->where('status', ArticleContent::STATUS_PENDING)
            ->oldest()
            ->limit($limit)
            ->get()
            ->each(function (ArticleContent $content) use ($service, &$processed): void {
                $service->check($content);
                $processed++;
            });

        $this->info("Processed {$processed} article content uniqueness checks.");

        return self::SUCCESS;
    }
}
