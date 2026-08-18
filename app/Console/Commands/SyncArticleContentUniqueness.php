<?php

namespace App\Console\Commands;

use App\Models\Articles\ArticleContent;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Services\Article\ArticleContentTextExtractor;
use App\Services\Article\ArticleContentUniquenessService;
use Illuminate\Console\Command;

class SyncArticleContentUniqueness extends Command
{
    protected $signature = 'app:sync-article-content-uniqueness
        {--limit= : Maximum translations to scan}
        {--locale=* : Locale to sync, can be passed multiple times}
        {--force : Mark matching translations as pending even if content hash was already checked}';

    protected $description = 'Create pending article content uniqueness checks for existing translations';

    public function handle(
        ArticleContentUniquenessService $service,
        ArticleContentTextExtractor $textExtractor
    ): int {
        $locales = $this->locales();
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $force = (bool) $this->option('force');

        $query = ArticleTranslation::query()
            ->whereIn('locale', $locales)
            ->where(function ($query): void {
                $query
                    ->whereNotNull('content')
                    ->orWhereNotNull('content_html');
            })
            ->orderBy('id');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $scanned = 0;
        $synced = 0;
        $skipped = 0;

        $query->get()->each(function (ArticleTranslation $translation) use (
            $service,
            $textExtractor,
            $force,
            &$scanned,
            &$synced,
            &$skipped
        ): void {
            $scanned++;

            if ($force) {
                $text = $textExtractor->extract($translation);

                if ($text === null) {
                    $skipped++;

                    return;
                }

                $content = ArticleContent::query()->firstOrNew([
                    'article_translation_id' => $translation->id,
                    'provider' => ArticleContent::PROVIDER_CONTENT_WATCH,
                ]);

                $content->fill([
                    'article_id' => $translation->article_id,
                    'locale' => $translation->locale,
                    'provider' => ArticleContent::PROVIDER_CONTENT_WATCH,
                ]);

                $content->markPending($textExtractor->hash($text));

                $synced++;

                return;
            }

            $content = $service->syncPendingForTranslation($translation);

            if ($content instanceof ArticleContent && $content->status === ArticleContent::STATUS_PENDING) {
                $synced++;

                return;
            }

            $skipped++;
        });

        $this->info("Scanned {$scanned} translations.");
        $this->info("Synced {$synced} pending uniqueness checks.");
        $this->info("Skipped {$skipped} translations.");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function locales(): array
    {
        $locales = $this->option('locale');

        if (is_array($locales) && $locales !== []) {
            return array_values(array_unique(array_map('strval', $locales)));
        }

        return array_values(config('article_content.enabled_locales', ['uk', 'ru']));
    }
}
