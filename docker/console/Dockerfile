# syntax=docker/dockerfile:1
###############################################################################
# Console image — queue worker and scheduler.
#
# Same application, no HTTP server. Run as two containers from this one build:
#
#   queue      CMD: php artisan queue:work      (RUN_MIGRATIONS=1 on this one)
#   scheduler  CMD: php artisan schedule:work
#
# Two containers rather than a supervisor: with one process each, the container
# runtime restarts a dead worker, which is what s6 was doing here before.
#
# It still needs the Vite build. Queue jobs render Blade to generate the static
# pages, and those layouts call @vite, which reads public/build/manifest.json.
#
# Build:  docker build -f docker/console/Dockerfile --secret id=composer_auth,src=auth.json .
###############################################################################

ARG FRANKENPHP_VERSION=1.12.3-php8.3-alpine
ARG NODE_VERSION=20-alpine

# ─────────────────────────────────────────────────────────────────────────────
# base — identical to the app image, from the same shared scripts.
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

# --no-scripts is required — see docker/app/Dockerfile:
# package:discover boots the framework, which loads routes/web.php, which
# queries the database. Discovery happens in the entrypoint instead.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ─────────────────────────────────────────────────────────────────────────────
# frontend — Vite build (needed for static page generation, see header).
# ─────────────────────────────────────────────────────────────────────────────
FROM node:${NODE_VERSION} AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources resources

RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# console — final image.
# ─────────────────────────────────────────────────────────────────────────────
FROM base AS console

ENV APP_ENV=production

# Kept identical to the app image. A Site saved from a console process also
# calls OctaneReloader; it correctly finds no server running in this container,
# but only if it resolves the right server type to ask about.
ENV OCTANE_SERVER=frankenphp

COPY --from=vendor /app /app
COPY --from=frontend /app/public/build /app/public/build

COPY docker/console/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
 && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# --max-time recycles the worker hourly so long-lived state cannot accumulate,
# the same reasoning as --max-requests on the web image.
CMD ["php", "artisan", "queue:work", \
     "--sleep=3", \
     "--tries=3", \
     "--max-time=3600"]
