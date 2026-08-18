#!/bin/sh
###############################################################################
# Console container entrypoint — queue worker and scheduler.
#
# This image is run as more than one container (queue + scheduler) from the
# same build. Only the one with RUN_MIGRATIONS=1 touches the schema, so the
# replicas cannot race each other.
###############################################################################
set -e

# Deferred from the build for the same reason as in the app image: discovery
# boots the framework, which loads routes/web.php, which queries the database.
php artisan package:discover --ansi

php artisan config:cache
php artisan view:cache

# Queue jobs render Blade to generate static pages, and LocalesSeeder writes
# locale icons to the public disk, so the console needs the symlink too.
php artisan storage:link --force

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force --no-interaction

    # Every seeder reachable from DatabaseSeeder is idempotent (updateOrCreate
    # / firstOrCreate / existence-checked inserts), so this converges on each
    # deploy rather than duplicating rows. Note that it also *reasserts* the
    # code-defined values: admin edits to roles, locales, markers and article
    # types are reverted on the next deploy by design.
    echo "[entrypoint] Running seeders..."
    php artisan db:seed --force --no-interaction
fi

exec "$@"
