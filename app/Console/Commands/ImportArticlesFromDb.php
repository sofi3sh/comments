<?php

namespace App\Console\Commands;

use App\Services\Article\ArticleImportService;
use Illuminate\Console\Command;

class ImportArticlesFromDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:import-db
                            {--type= : dict, publications, opinionsnew, users, persons, company, opinions_author}
                            {--limit= : Total number of articles to import}
                            {--batch=100 : Number of articles per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import articles from old database into the new structure';


    /**
     * Execute the console command.
     */
    public function handle(ArticleImportService $importService): int
    {
        $typeOption = $this->option('type');
        $batchLimit = (int)($this->option('batch') ?: 100);
        $totalLimit = $this->option('limit') !== null ? (int)$this->option('limit') : null;

        $this->info('[IMPORT] [ARTICLES] Запуск імпорту з консолі...');
        $this->info(sprintf('Параметри: type=%s batch=%d, limit=%s', $typeOption, $batchLimit, $totalLimit ?? '∞'));

        try {
            $start = microtime(true);

            $result = match ($typeOption) {
                'dict'            => $importService->importDicts(),
                'users'           => $importService->importUsers(),
                'opinions_author' => $importService->importOpinionAuthors(),
                'publications'    => $importService->importArticles('publications',$batchLimit, $totalLimit),
                'opinionsnew'     => $importService->importArticles('opinionsnew',$batchLimit, $totalLimit),
                'persons'         => $importService->importArticles('persons', $batchLimit, $totalLimit),
                'company'         => $importService->importArticles('company', $batchLimit, $totalLimit),
                default => throw new \InvalidArgumentException("Unknown type: {$typeOption}"),
            };

            $this->info('');
            $this->info('===== ІМПОРТ ЗАВЕРШЕНО =====');
            $this->info(sprintf('Час: %s сек', round(microtime(true) - $start, 2)));


            if (!empty($result['stats'])) {
                $this->info('===== РЕЗУЛЬТАТ =====');
                $this->line("Обработано: {$result['total_processed']}");
                $this->line("Импортировано: {$result['imported']}");
                $this->line("Пропущено: {$result['skipped']}");
                $this->line("Батчей: {$result['batches']}");
                $this->line("Ошибок: " . count($result['errors']));
            }

            if (!empty($result['errors'])) {
                $this->warn('===== ОШИБКИ =====');
                foreach ($result['errors'] as $error) {
                    $this->error(json_encode($error, JSON_UNESCAPED_UNICODE));
                }
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('===== IMPORT ERROR =====');
            $this->error(json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
            return self::FAILURE;
        }
    }


    /** IMPORT FLOW  */
/**
    db require at least one SITE
    db require migrations

    -- DB truncate-- (except sites and migrations)
    php artisan cache:clear
    php artisan db:seed
    php artisan articles:import-db --type=dict
    php artisan articles:import-db --type=users
    php artisan articles:import-db --type=opinions_author
    php artisan articles:import-db --type=publications
    php artisan articles:import-db --type=opinionsnew
    php artisan articles:import-db --type=persons
    php artisan articles:import-db --type=company

    php artisan db:seed --class=BeginSeeder    (policy page & AdminUser)
*/

}