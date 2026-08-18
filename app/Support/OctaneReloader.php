<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gracefully restarts the Octane workers so boot-time state is rebuilt.
 *
 * routes/web.php constrains the frontend route group to Site::getCachedDomains().
 * Route registration runs once per worker boot, so clearing the domain cache is
 * not sufficient on its own: the already-compiled `whereIn('domain', ...)` keeps
 * the old list for the life of the worker. Without a reload, adding or renaming
 * a site 404s until the next deploy — and workers that booted before the first
 * Site existed would stay pinned to the `$never` fallback forever.
 *
 * Two deliberate properties:
 *
 *  - Synchronous, never queued. Queued jobs run in the console container, which
 *    has no HTTP workers to reload. This has to execute in whichever container
 *    served the request that changed the site.
 *  - Never throws. A missed reload degrades to stale routes until the next
 *    deploy; it must not roll back or fail the write that triggered it.
 */
final class OctaneReloader
{
    public function reload(string $reason): void
    {
        // Not running under Octane (classic mode, or the package isn't
        // installed yet): nothing is holding stale routes, so there is nothing
        // to reload. Checking registration also keeps this from throwing
        // CommandNotFoundException before Octane is adopted.
        if (! array_key_exists('octane:reload', Artisan::all())) {
            return;
        }

        try {
            $exitCode = Artisan::call('octane:reload');

            if ($exitCode === 0) {
                Log::info('Octane workers reloaded', ['reason' => $reason]);

                return;
            }

            // Non-zero means no Octane server is running in this container.
            // That is the normal case in classic mode and in the console
            // container, so it is not worth an info-level line.
            Log::debug('octane:reload skipped: no server running here', [
                'reason' => $reason,
                'exit_code' => $exitCode,
            ]);
        } catch (Throwable $e) {
            Log::warning('octane:reload failed', [
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
