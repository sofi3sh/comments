<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | User uploads
        |----------------------------------------------------------------------
        |
        | Attachments, avatars, site logos, locale icons. Still named "public"
        | because that name is baked into every Storage::disk('public') call and
        | every Backpack `'disk' => 'public'` field — but it is object storage
        | now, not storage/app/public: MinIO locally, Hetzner Object Storage in
        | staging/prod. Nothing is written to the local filesystem, and there is
        | no public/storage symlink behind it any more.
        |
        | `url` is the browser-facing nginx host, NOT the S3 endpoint. nginx
        | proxies /storage/{key} into this bucket (docker/nginx/conf.d/
        | platform.conf), so the object store never has to be published — the
        | same arrangement the static disks below use.
        |
        | Connection settings fall back to the STATIC_S3_* pair: locally, and on
        | Hetzner, uploads live in the same account as the static buckets. Set
        | the UPLOADS_S3_* vars to split them onto their own endpoint or key.
        |
        | No `visibility` here on purpose — see the static disks below for why.
        |
        */

        'public' => [
            'driver' => env('UPLOADS_DISK', 's3'),
            'root' => storage_path('app/public'),
            'key' => env('UPLOADS_S3_KEY', env('STATIC_S3_KEY')),
            'secret' => env('UPLOADS_S3_SECRET', env('STATIC_S3_SECRET')),
            'region' => env('UPLOADS_S3_REGION', env('STATIC_S3_REGION', 'us-east-1')),
            'bucket' => env('UPLOADS_BUCKET', 'uploads'),
            'url' => env('UPLOADS_URL', env('APP_URL').'/storage'),
            'endpoint' => env('UPLOADS_S3_ENDPOINT', env('STATIC_S3_ENDPOINT')),
            'use_path_style_endpoint' => (bool) env('UPLOADS_S3_PATH_STYLE', env('STATIC_S3_PATH_STYLE', true)),
            'visibility' => 'public',
            'serve' => true,
            // A failed upload used to be a silent `false` from the local disk.
            // Over the network it is a network error worth surfacing, so the
            // request fails instead of writing an attachment row with no file.
            'throw' => true,
            'report' => true,
        ],

        /*
        |----------------------------------------------------------------------
        | Basset cache
        |----------------------------------------------------------------------
        |
        | Backpack's vendored admin CSS/JS (BASSET_DISK). It defaults to the
        | `public` disk, which is object storage now — and Basset is not S3-safe:
        | Helpers\CacheMap and BassetManager::bassetArchive() both call
        | $disk->path() and hand the result to File::put() / the unarchiver. On
        | an S3 disk path() does not fail, it returns the bare object key — so
        | those writes land on a *relative* local path (base_path()/basset/…)
        | that nothing serves, and the cache map silently never persists. The
        | breakage is quiet, and it only starts once cache_map turns itself on
        | (APP_ENV=production). So Basset gets a local disk of its own.
        |
        | These are build artefacts, not user data: they belong on the container
        | filesystem and have no business in the uploads bucket.
        |
        | `serve` registers GET /basset/{path} (see FilesystemServiceProvider),
        | which is what makes the url below resolve. It works only because the
        | app deliberately never runs route:cache — see docker/app/entrypoint.sh.
        | nginx routes ^~ /basset/ to the app for this.
        |
        */

        'basset' => [
            'driver' => 'local',
            'root' => storage_path('app/basset'),
            'url' => env('APP_URL').'/basset',
            'visibility' => 'public',
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Static mode disks
        |----------------------------------------------------------------------
        |
        | Generated pages ("static mode") live in S3-compatible object storage:
        | MinIO locally, Hetzner Object Storage in staging/prod. The app writes
        | pre-brotli-compressed objects here and nginx proxies reads to the
        | bucket — neither side needs a shared filesystem.
        |
        | Every write carries ContentType / ContentEncoding / CacheControl and an
        | `x-amz-meta-last-modified` holding the DB timestamp; see
        | App\Services\Article\StaticFileService.
        |
        | No `visibility` here on purpose. Laravel would translate it into a
        | per-object `ACL: public-read`, which Ceph RGW (Hetzner) may reject or
        | ignore — and it is redundant: both buckets are anonymous-read by bucket
        | policy, with access control for private content living in nginx's
        | auth_request → /api/auth.
        |
        */

        'static-public' => [
            'driver' => 's3',
            'key' => env('STATIC_S3_KEY'),
            'secret' => env('STATIC_S3_SECRET'),
            'region' => env('STATIC_S3_REGION', 'us-east-1'),
            'bucket' => env('STATIC_PUBLIC_BUCKET'),
            'endpoint' => env('STATIC_S3_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('STATIC_S3_PATH_STYLE', true),
            // Unlike the local disk, a failed write is a network error worth
            // retrying — let it surface so the queue job fails and retries.
            'throw' => true,
            'report' => true,
        ],

        'static-private' => [
            'driver' => 's3',
            'key' => env('STATIC_S3_KEY'),
            'secret' => env('STATIC_S3_SECRET'),
            'region' => env('STATIC_S3_REGION', 'us-east-1'),
            'bucket' => env('STATIC_PRIVATE_BUCKET'),
            // Browser-facing nginx host (content.*), NOT the S3 endpoint.
            // ArticleContentService::getRestUrl() builds `data-rest-url` from
            // this; pointing it at the bucket would bypass the auth subrequest
            // and hand out paywalled fragments. Never call ->url() on this disk.
            'url'  => env('STATIC_PRIVATE_URL'),
            'endpoint' => env('STATIC_S3_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('STATIC_S3_PATH_STYLE', true),
            'throw' => true,
            'report' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
        | Deliberately empty for object storage: the `public` disk writes to S3
        | and nginx serves /storage/ straight from the bucket. When the local
        | uploads disk is enabled, storage:link restores Laravel's public symlink.
    |
    */

    'links' => env('UPLOADS_DISK') === 'local'
        ? [
            public_path('storage') => storage_path('app/public'),
        ]
        : [],

];
