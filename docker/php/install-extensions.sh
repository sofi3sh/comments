#!/bin/sh
###############################################################################
# PHP extension set, shared by the app and console images.
#
# Deliberately one file for both roles. The two images must not drift: brotli
# is only *called* by the console (StaticFileService::brotliCompress() throws
# outright when the extension is missing, so every static-generation job would
# fail), but keeping a single list means moving work between roles cannot
# silently break it.
###############################################################################
set -eu

# brotli has no install-php-extensions recipe, so it comes from PECL. The
# runtime library is installed separately from the headers, otherwise removing
# the build deps below would take it with them.
apk add --no-cache brotli-libs curl

apk add --no-cache --virtual .build-deps brotli-dev ${PHPIZE_DEPS}
pecl install brotli
docker-php-ext-enable brotli
apk del .build-deps

install-php-extensions \
    bcmath \
    exif \
    gd \
    imagick \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    redis \
    zip
