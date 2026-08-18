<?php

namespace App\Console\Commands;

use App\Services\StaticCache\ManualStaticPublicInvalidator;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ManualStaticPublicInvalidateCommand extends Command
{

    /**
     *                   E x a m p l e s
     * php artisan static:manual-invalidate-public tags --dry-run
     * php artisan static:manual-invalidate-public tags --dry-run
     * php artisan static:manual-invalidate-public all --dry-run --limit=10
     */

    protected $signature = 'static:manual-invalidate-public
                            {type=all : Invalidation type}
                            {--dry-run : Show matched files without deleting them}
                            {--limit=50 : Maximum number of paths to print}';

    protected $description = 'Manually invalidate public static files by type';

    public function handle(ManualStaticPublicInvalidator $invalidator): int
    {
        $type = (string) $this->argument('type');
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        try {
            $result = $invalidator->invalidate($type, $dryRun);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            $this->line('Available types: ' . implode(', ', $invalidator->types()));

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s %d public static file(s) for type [%s].',
            $result->dryRun ? 'Matched' : 'Invalidated',
            $result->count(),
            $result->type
        ));

        if ($result->blindCount() > 0) {
            $this->comment(sprintf(
                '%d of these come from exact patterns and are deleted without an existence check, '
                . 'so some may never have existed.',
                $result->blindCount()
            ));
        }

        if ($result->paths !== [] && $limit > 0) {
            $this->line('');
            $this->table(
                ['Path'],
                array_map(static fn (string $path): array => [$path], array_slice($result->paths, 0, $limit))
            );
        }

        if ($result->count() > $limit) {
            $this->line(sprintf('...and %d more.', $result->count() - $limit));
        }

        if ($result->failed !== []) {
            $this->error(sprintf('Failed to delete %d file(s).', count($result->failed)));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
