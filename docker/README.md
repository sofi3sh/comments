# Docker images

Two images are built from this repository:

| Entry | Image | Process |
|---|---|---|
| `docker/app/Dockerfile` | web | FrankenPHP serving Laravel Octane in worker mode, plain HTTP on `:80` |
| `docker/console/Dockerfile` | console | `queue:work` (default) or `schedule:work` |

There is no nginx, no php-fpm and no s6-overlay. FrankenPHP terminates HTTP and
executes PHP in one process, so nothing is left to supervise; the console runs
one process per container and lets the container runtime handle restarts.

## TLS

Neither image contains a certificate. The web image serves plain HTTP and
expects TLS to be terminated upstream (reverse proxy).

## Migrations

Only the container with `RUN_MIGRATIONS=1` migrates or seeds; set it on the
queue service and nowhere else. The web image never touches the schema, so the
web tier can scale freely.

Seeding runs on every deploy. Every seeder reachable from `DatabaseSeeder` is
idempotent, but it also *reasserts* code-defined values — admin edits to roles,
locales, markers and article types are reverted on the next deploy by design.

## Route cache

`route:cache` is deliberately not run. `routes/web.php` compiles the site domain
list into the frontend route group, and `App\Support\OctaneReloader` restarts
the workers when a `Site` is created, renamed or deleted. A cached routes file
would survive that restart and pin the stale list until the next deploy. If it
is ever added, `OctaneReloader` must rebuild it before signalling the reload.

## Boot-time failures are fatal in worker mode

Under Octane the worker boots the framework once. Anything that throws during
that boot — a service provider, a missing config value — takes down the whole
container rather than returning a 500 for one request. FrankenPHP reports it as
`worker ... has not reached frankenphp_handle_request()` and the server exits.

In particular, `AppServiceProvider::boot()` asserts the static-file disks are
configured, so **these must be present in the deployed `.env` or the container
crash-loops**:

    STATIC_S3_ENDPOINT
    STATIC_S3_KEY
    STATIC_S3_SECRET
    STATIC_PUBLIC_BUCKET
    STATIC_PRIVATE_BUCKET
    STATIC_PRIVATE_URL

`STATIC_PRIVATE_URL` is the browser-facing nginx host for private fragments
(`content.*`), **not** the S3 endpoint — `ArticleContentService::getRestUrl()`
builds `data-rest-url` from it, and pointing it at the bucket would hand out
paywalled content without the auth subrequest.

The buckets must exist and be readable before the first capture or warm run.
No filesystem mounts are involved any more; see `docs/static-s3-deployment.md`.

## Server-side compose

The deploy script (`docker/deploy.sh`) expects these services in
`/opt/docker/docker-compose.yml`. Service names are overridable via
`APP_SERVICE`, `QUEUE_SERVICE` and `SCHEDULER_SERVICE`.

```yaml
x-app-common: &app-common
  env_file: [./env/.env.miss-api]
  # Both entrypoints run artisan (package:discover, config:cache), so the
  # container needs a reachable database at start, not just at first request.
  # The restart policy is what rides out a database that is still coming up.
  restart: unless-stopped
  volumes:
    - ./storage/miss-api:/app/storage          # NOTE: /app, not /var/www
    # No static mounts: generated pages go to object storage (STATIC_S3_*).

x-console: &console
  <<: *app-common
  image: registry.gitlab.com/<group>/<project>/main/console:latest

services:
  miss-api:
    <<: *app-common
    image: registry.gitlab.com/<group>/<project>/main/app:latest
    ports:
      - "8080:80"          # upstream proxy terminates TLS and forwards here

  miss-queue:
    <<: *console
    environment:
      RUN_MIGRATIONS: "1"   # exactly one service may set this

  miss-scheduler:
    <<: *console
    command: ["php", "artisan", "schedule:work"]
```

`.env` must set `OCTANE_SERVER=frankenphp`; `config/octane.php` otherwise
defaults to `roadrunner` and `octane:reload` would target the wrong server.
