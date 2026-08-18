<?php

namespace App\Providers;

use App\Editor\Blocks\Gallery;
use App\Observers\ArticleStaticObserver;
use App\Repositories\ArticleTypeRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\Interfaces\ArticleTypeRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\Articles\Article;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\User\Translate\UserTranslation;
use App\Models\User\User;
use App\Observers\ArticleObserver;
use App\Observers\ArticleTranslationObserver;
use App\Observers\UserObserver;
use App\Observers\UserTranslationObserver;
use App\SEO\SchemaGraph;
use App\SEO\SeoManager;
use App\Services\Translation\Contracts\TranslationProvider;
use App\Services\Translation\GoogleTranslationProvider;
use App\Support\LanguageSwitcherStore;
use App\Support\LastModifiedStore;
use InvalidArgumentException;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Both packages are in composer.json `dont-discover`, so this is the
        // only place they get registered.
        $this->app->booted(function (): void {
            $this->app->register(\Backpack\CRUD\BackpackServiceProvider::class);
            $this->app->register(\Backpack\Pro\AddonServiceProvider::class);
        });

        // scoped(): fresh instance per request under Octane/FrankenPHP,
        // so request-level SEO/schema state never leaks between requests.
        $this->app->scoped(SchemaGraph::class);
        $this->app->scoped(SeoManager::class);
        $this->app->scoped(LastModifiedStore::class);
        $this->app->scoped(LanguageSwitcherStore::class);

        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class,
        );
        $this->app->bind(
            ArticleTypeRepositoryInterface::class,
            ArticleTypeRepository::class
        );

        $this->app->bind(TranslationProvider::class, function (): TranslationProvider {
            return match (config('article_translation.provider')) {
                'google' => app(GoogleTranslationProvider::class),
                default => throw new InvalidArgumentException('Unsupported article translation provider.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'local' && ! $this->app->runningInConsole()) {
            URL::forceScheme('https');
        }

        if (! $this->app->runningInConsole()) {
            // NB: use_path_style_endpoint is a bool and would fail the
            // is_string() check below — do not add it here.
            $this->assertConfigured([
                'filesystems.disks.static-public.bucket',
                'filesystems.disks.static-private.bucket',
                'filesystems.disks.static-private.url',
                'filesystems.disks.static-public.endpoint',
                'filesystems.disks.static-public.key',
                'filesystems.disks.static-public.secret',
                // The uploads disk ("public"). Its key/secret default to the
                // static pair above, but bucket and url are its own — and a
                // blank UPLOADS_BUCKET= in .env beats the config default,
                // giving an empty bucket name and a 404 per image.
                'filesystems.disks.public.bucket',
                'filesystems.disks.public.url',
                'filesystems.disks.public.endpoint',
            ]);
        }

        EditorPhp::register([
            'gallery' => Gallery::class,
        ]);

        Article::observe([
            ArticleObserver::class,
            ArticleStaticObserver::class,
        ]);

        ArticleTranslation::observe(ArticleTranslationObserver::class);
        User::observe(UserObserver::class);
        UserTranslation::observe(UserTranslationObserver::class);
    }

    /**
     * Fail fast when a required config value is missing or blank.
     *
     * Asserts on config() rather than env(): the values come from env() inside
     * config/filesystems.php, which is the only place env() is readable once
     * `php artisan config:cache` has run. Asserting on env() directly here
     * aborted every boot of a config-cached app.
     *
     * @param  list<string>  $keys
     */
    private function assertConfigured(array $keys): void
    {
        foreach ($keys as $key) {
            $value = config($key);

            if (! is_string($value) || trim($value) === '') {
                throw new RuntimeException("no {$key} set");
            }
        }
    }
}
