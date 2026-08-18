#!/bin/sh
###############################################################################
# Web container entrypoint.
#
# Deliberately runs no migrations. The console image owns schema changes, so
# the web tier can be scaled to any number of replicas without them racing to
# migrate the same database.
###############################################################################
set -e

# Package discovery runs here rather than during the build. It boots the
# framework, which loads routes/web.php, which queries the database for the site
# domain list — so it needs a reachable database, which only exists at runtime.
#
# The same is true of config:cache and view:cache below: this container will not
# start until the database is up. That is a real ordering dependency, so the
# compose service needs a restart policy (or depends_on) to ride out a database
# that is still coming up.
php artisan package:discover --ansi

# Caches are built at boot rather than at build time: they bake in the runtime
# environment, and .env is mounted at deploy time, not present during the build.
php artisan config:cache
php artisan view:cache

# NOT route:cache — on purpose.
#
# routes/web.php compiles the site domain list (Site::getCachedDomains()) into
# the frontend route group, and App\Support\OctaneReloader restarts the workers
# when that list changes so they pick it up. A cached routes file would survive
# that restart and pin the stale domain list until the next deploy, silently
# 404ing every newly added site.
#
# Worker mode already amortises route registration across every request a
# worker serves, so the cache would buy very little here anyway. If it is ever
# added, OctaneReloader must rebuild it before signalling the reload.

# storage/ is a mounted volume, so the symlink is created at boot rather than
# baked into the image where the mount would hide it.
php artisan storage:link --force

exec "$@"
