<?php

namespace App\Console\Commands;

use App\Services\Article\ArticleViewsService;
use Illuminate\Console\Command;

class ProcessArticleViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-article-views';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for processing articles views';


    /**
     * Execute the console command.
     */
    public function handle(ArticleViewsService $service)
    {
        $count = $service->processStream();
        $this->info("Processed {$count} views.");
    }
}
