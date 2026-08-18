# syntax=docker/dockerfile:1
###############################################################################
# Web application image — FrankenPHP running Laravel Octane in worker mode.
#
# Replaces the old nginx + php-fpm + s6-overlay image. FrankenPHP is a single
# process that both terminates HTTP and executes PHP, so there is no longer a
# second process to supervise and s6 has nothing left to do.
#
# Serves plain HTTP on :80. TLS is terminated upstream, so no certificate ever
# enters this image.
#
# Build:  docker build -f docker/app/Dockerfile --secret id=composer_auth,src=auth.json .
###############################################################################

ARG FRANKENPHP_VERSION=1.12.3-php8.3-alpine
ARG NODE_VERSION=20-alpine

# ─────────────────────────────────────────────────────────────────────────────
# base — PHP runtime and extensions. Shared by the vendor and final stages so
# Composer resolves platform requirements against the same PHP that runs.
# ─────────────────────────────────────────────────────────────────────────────
FROM dunglas/frankenphp:${FRANKENPHP_VERSION} AS base

COPY docker/php/install-extensions.sh /tmp/install-extensions.sh
RUN chmod +x /tmp/install-extensions.sh \
 && /tmp/install-extensions.sh \
 && rm /tmp/install-extensions.sh

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /app

# ─────────────────────────────────────────────────────────────────────────────
# vendor — production Composer dependencies.
# ─────────────────────────────────────────────────────────────────────────────
FROM base AS vendor

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Manifests first so this layer is reused whenever only app code changed.
COPY composer.json composer.lock ./

RUN --mount=type=secret,id=composer_auth \
    mkdir -p /root/.composer \
 && cp /run/secrets/composer_auth /root/.composer/auth.json \
 && composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
 && rm -f /root/.composer/auth.json

COPY . .

# --no-scripts is required. post-autoload-dump would run package:discover,
# which boots the framework, which loads routes/web.php, which calls
# Site::getCachedDomains() — a database query. There is no database
# during a build, so discovery is deferred to the entrypoint instead.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ─────────────────────────────────────────────────────────────────────────────
# frontend — Vite build.
# ─────────────────────────────────────────────────────────────────────────────
FROM node:${NODE_VERSION} AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources resources

RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# app — final image.
# ─────────────────────────────────────────────────────────────────────────────
FROM base AS app

ENV APP_ENV=production

# config/octane.php defaults the server to roadrunner. The CMD below passes
# --server=frankenphp explicitly so startup is unaffected, but `octane:reload`
# takes no such option and falls back to config('octane.server'). Getting that
# wrong fails silently: App\Support\OctaneReloader would report success while
# reloading nothing, and new site domains would 404 until the next deploy.
# Baking the default in means a deployed .env cannot forget it. An explicit
# OCTANE_SERVER in the environment still overrides this.
ENV OCTANE_SERVER=frankenphp

COPY --from=vendor /app /app
COPY --from=frontend /app/public/build /app/public/build

# Octane installs its FrankenPHP worker script into public/ on first boot.
# Doing it here instead keeps the running container from writing into its own
# document root, and keeps first-request latency off the critical path.
RUN cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php \
       public/frankenphp-worker.php

COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# --admin-port is mandatory here. Octane derives it as 2019 + (port - 8000),
# which is negative for port 80 and aborts startup. The admin API is also
# what `octane:reload` drives (Octane PATCHes the Caddy admin config endpoint),
# so App\Support\OctaneReloader — and therefore picking up new site domains
# without a redeploy — depends on this being reachable. It binds to
# localhost inside the container only.
#
# --max-requests recycles each worker periodically, which bounds any slow
# growth in long-lived worker state.
CMD ["php", "artisan", "octane:start", \
     "--server=frankenphp", \
     "--host=0.0.0.0", \
     "--port=80", \
     "--admin-port=2019", \
     "--max-requests=500"]
