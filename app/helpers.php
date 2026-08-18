<?php

use App\Models\Articles\Article;
use App\Services\Settings\SettingsService;
use App\Support\LastModifiedStore;
use App\Support\Permissions\CrudOperation;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\NullHandler;
use Psr\Log\NullLogger;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

require_once __DIR__.'/../vendor/backpack/crud/src/helpers.php';

if (! function_exists('setLastMod')) {
    function setLastMod(mixed $date): void
    {
        app(LastModifiedStore::class)->set($date);
    }
}

if (!function_exists('is_backpack_admin')) {

    function is_backpack_admin(?\App\Models\User\User $user = null, string $guard = 'web'): bool
    {
        $user ??= backpack_user();

        return (bool) $user?->hasRole('Admin', $guard);
    }
}

if (!function_exists('has_crud_permission')) {

    function has_crud_permission(string $modelName, string $operation, string $adminRoleName = 'Admin', string $guard = 'web'): bool
    {
        $user = backpack_user();

        if (!$user) {
            return false;
        }

        $isAdmin = $user->hasRole($adminRoleName, $guard);

        if ($isAdmin) {
            return true;
        }

        $permissions = ["{$modelName}.{$operation}"];

        if ($modelName === 'article' && in_array($operation, CrudOperation::ARTICLE_OWN_CAPABLE, true)) {
            $permissions[] = "{$modelName}.{$operation}-own";
        }

        foreach ($permissions as $permission) {
            try {
                if ($user->hasPermissionTo($permission, $guard)) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
                continue;
            }
        }

        return false;
    }
}

if (!function_exists('get_tinymce_plugins')) {
    function get_tinymce_plugins(): string
    {
        return config('tinymce.plugins', 'image,link,media,anchor,table,lists');
    }
}

if (!function_exists('get_tinymce_toolbar')) {
    function get_tinymce_toolbar(): string
    {
        return config('tinymce.toolbar', 'undo redo | formatselect | bold italic | link image | table | bullist numlist');
    }
}

if (!function_exists('page_article_url')) {
    function page_article_url(string $title, ?string $locale = null): ?string
    {
        try {
            $locale = $locale ?? app()->getLocale();

            $typeId = \App\Models\Articles\ArticleType::query()
                ->where('code', 'page')
                ->value('id');

            if ($typeId === null) {
                return null;
            }

            $baseQuery = \App\Models\Articles\Article::query()
                ->where('type_id', $typeId);

            $article = (clone $baseQuery)
                ->whereHas('translations', function ($q) use ($title, $locale) {
                    $q->where('locale', $locale)
                        ->where('title', $title);
                })
                ->first();

            if ($article === null) {
                $article = $baseQuery
                    ->whereHas('translations', function ($q) use ($title) {
                        $q->where('locale', 'uk')
                            ->where('title', $title);
                    })
                    ->first();
            }

            if ($article === null) {
                return null;
            }

            return $article->getArticleUrlForLocale($locale);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('parseFio')) {
    function parseFio(?string $fio): array
    {
        if (!$fio) {
            return [null, null];
        }

        // чистим строку
        $fio = trim(preg_replace('/\s+/', ' ', $fio));

        if ($fio === '') {
            return [null, null];
        }

        $parts = explode(' ', $fio);

        // 1 слово
        if (count($parts) === 1) {
            return [$parts[0], null];
        }

        // 2 слова (идеальный случай)
        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }

        // 3+ слов (ФИО)
        // считаем:
        // имя = первое слово
        // фамилия = всё остальное
        $name = array_shift($parts);
        $surname = implode(' ', $parts);

        return [$name, $surname];
    }
}


if (! function_exists('logTo')) {

    function logTo(?string $channel = null)
    {
        $channel ??= config('logging.default');

        if (! config("logging.channels.$channel.enabled", true)) {
            return new NullLogger();
        }

        return Log::channel($channel);
    }
}

if (! function_exists('is_admin_request')) {

    /**
     * Is the current request served by the Backpack admin panel?
     *
     * The admin panel lives on a path prefix (config `backpack.base.route_prefix`)
     * of the same hosts as the frontend, so site-scoping global scopes need this
     * to tell the two apart.
     */
    function is_admin_request(): bool
    {
        $prefix = trim((string) config('backpack.base.route_prefix'), '/');

        if ($prefix === '') {
            return false;
        }

        return request()->is($prefix, $prefix.'/*');
    }
}

if (! function_exists('required_env')) {

    function required_env(string $key): string
    {
        $value = env($key);

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException("no {$key} set");
        }

        return $value;
    }
}

if (! function_exists('local_testing_log_channel')) {

    function local_testing_log_channel(string $filename): array
    {
        if (env('APP_ENV') !== 'local') {
            return [
                'driver' => 'monolog',
                'handler' => NullHandler::class,
            ];
        }

        return [
            'driver' => 'single',
            'level' => 'info',
            'path' => storage_path("logs/{$filename}.log"),
        ];
    }
}


if (! function_exists('articleContentCacheKey')) {

    function articleContentCacheKey(
        Article $article,
        string $mode,
        ?string $locale = null,
    ): string
    {
        $prefix = 'article-content';
        return implode(':', [
            $prefix,
            $mode,
            $locale ?? app()->getLocale(),
            $article->getKey() ?? 'new'
        ]);
    }
}


if (! function_exists('brotliPath')) {

    function brotliPath(string $path): string
    {
        return $path.'.br';
    }
}

if (! function_exists('restPath')) {

    function restPath(string $locale, int $id): string
    {
        return "$locale/$id.html";
    }
}

if (! function_exists('sitePath')) {

    /**
     * Public static files are stored per site host so the same URI can have
     * different content on each subdomain (robots.txt, sitemaps, articles).
     */
    function sitePath(string $host, string $path): string
    {
        return 'sites/'.$host.'/'.ltrim($path, '/');
    }
}

if (! function_exists('settings')) {
    function settings(): SettingsService
    {
        return app(SettingsService::class);
    }
}
