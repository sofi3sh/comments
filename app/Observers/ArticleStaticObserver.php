<?php

namespace App\Observers;

use App\Models\Articles\Article;
use App\Services\Article\StaticInvalidateService;
use App\Support\LogChannels;
use Illuminate\Support\Facades\DB;

class ArticleStaticObserver
{
    protected bool $toLog;
    public function __construct(
        private readonly StaticInvalidateService $staticInvalidateService,
    ) {
        $this->toLog = (bool) config('logging.test.info', false);
    }

    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        $this->toLog && logTo(LogChannels::DEBUG)->info('Created ArticleStaticObserver');
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        if (!$this->shouldInvalidateStatic($article)) {
            return;
        }

        $this->toLog && logTo(LogChannels::DEBUG)->info('UPDATED ArticleStaticObserver');

        $this->invalidateAfterCommit($article);
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        $this->toLog && logTo(LogChannels::DEBUG)->info('DELETED ArticleStaticObserver');

        $this->invalidateAfterCommit($article);
    }

    /**
     * Handle the Article "restored" event.
     */
    public function restored(Article $article): void
    {
        $this->invalidateAfterCommit($article);
    }

    /**
     * Handle the Article "force deleted" event.
     */
    public function forceDeleted(Article $article): void
    {
        $this->invalidateAfterCommit($article);
    }

    private function shouldInvalidateStatic(Article $article): bool
    {
        return $article->wasChanged([
            'category_id',
            'type_id',
            'status',
            'published_at',
            'source_url',
            'is_media',
            'do_follow',
        ]);
    }

    private function invalidateAfterCommit(Article $article): void
    {
        DB::afterCommit(function () use ($article): void {
            $this->staticInvalidateService->invalidateArticle($article);
        });
    }
}
