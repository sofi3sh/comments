<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\HtmlFragmentsController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\Legacy\LegacyArticleRedirectController;
use App\Http\Controllers\Legacy\LegacyContributorRedirectController;
use App\Http\Controllers\Legacy\LegacyDossierRedirectController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TagController;
use App\Http\Middleware\SeoStaticCaptureMiddleware;
use App\Http\Middleware\StaticCaptureMiddleware;
use App\Http\Middleware\DetectLocale;
use App\Http\Middleware\DetectLocaleOld;
use App\Http\Middleware\ValidateTurnstile;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Category;
use App\Models\Settings\Locale;
use App\Models\Site\Site;
use App\Support\AuthUrls;
use App\Support\PageRoles;
use Illuminate\Support\Facades\Route;


// A route requirement may never be an empty string — Symfony throws
// "Routing requirement for X cannot be empty" while *matching*, so an empty
// list here turns every unmatched request (e.g. any admin-panel URL) into a
// 500 rather than a 404. `(?!)` is a never-matching pattern, which is the
// correct meaning when the list is empty.
$never = '(?!)';

$locales = Locale::getLocalesForRoute() ?: $never;
$subcategories = Category::allForRoute();
$domains = Site::getCachedDomains() ?: [$never];
$mainDomain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $never;
$categoryDomains = array_values(array_diff($domains, [$mainDomain])) ?: [$never];
$articleTypes = ArticleType::getForRoute() ?: $never;
$articleCategoryTypes = ArticleType::getForRoute(true) ?: $never;
$bpBaseWebMiddleware = config('backpack.base.web_middleware', 'web');
$bpAuthMiddleware = 'auth:'.config('backpack.base.guard');

// backpack.auth.login / backpack.auth.logout live under the admin prefix —
// see the auth group at the bottom of routes/backpack/custom.php.

Route::pattern('slug', '[A-Za-z0-9\-]+');
Route::pattern('id', '[0-9]+');

Route::prefix('{locale}')
    ->where(['locale' => $locales])
    ->name('locale.')

    ->group(function () {
        Route::get('/footer/categories', [HtmlFragmentsController::class, 'footerCategories'])
            ->name('footer.categories');

        Route::get('/footer/pages', [HtmlFragmentsController::class, 'footerPages'])
            ->name('footer.pages');

        Route::get('/fragments/articles-with-actions', [HtmlFragmentsController::class, 'articlesWithActions'])
            ->name('fragments.articles-with-actions');
    });

// Main site: Ukrainian homepage is the domain root; all other public
// sections below are available only here.
Route::domain($mainDomain)
    ->middleware([DetectLocale::class])
    ->group(function () use ($locales, $articleTypes, $articleCategoryTypes) {
        Route::get('/', [HomePageController::class, 'homepage'])
            ->name('homepage');

        Route::get('/{locale}', [HomePageController::class, 'homepage'])
            ->where('locale', 'ru|en')
            ->name('locale.homepage');

        Route::permanentRedirect('/ua', '/');

        Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
            ->whereIn('provider', ['google', 'facebook'])
            ->name('social.redirect');

        Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
            ->whereIn('provider', ['google', 'facebook'])
            ->name('social.callback');

        Route::prefix('{locale}')
            ->where(['locale' => $locales])
            ->name('locale.')
            ->group(function () use ($articleTypes, $articleCategoryTypes){
                Route::get('/dossier', [CategoryController::class, 'dossier'])
                    ->name('dossier');

                Route::get('/dossier/{type?}/{letter?}', [CategoryController::class, 'significant'])
                    ->name('significant');

                Route::get('editors', [ContributorController::class, 'editors'])
                    ->name('editors');

                Route::get('author/{slug}-{id}.html', [ContributorController::class, 'author'])
                    ->name('author');

                Route::get('editor/{slug}-{id}.html', [ContributorController::class, 'editor'])
                    ->name('editor');

                Route::get('/tag/{slug}', [TagController::class, 'show'])
                    ->name('tag.show');

                Route::get('/collection/{code}', [CollectionController::class, 'show'])
                    ->name('collection.show');

                Route::get('/{typecat}', [ArticleController::class, 'showByCategoryList'])
                    ->where('typecat', $articleCategoryTypes)
                    ->name('category.type.show.list');

                Route::get('/{type}', [ArticleController::class, 'showByTypeList'])
                    ->where('type', $articleTypes)
                    ->name('type.show.list');
            });
    });

// Category domains: their legacy root was Russian, while /ua was Ukrainian.
Route::domain('{domain}')
    ->whereIn('domain', $categoryDomains)
    ->middleware([DetectLocale::class])
    ->group(function () {
        Route::permanentRedirect('/', '/ru');
        Route::permanentRedirect('/ua', '/uk');

        Route::get('/{locale}', [HomePageController::class, 'homepage'])
            ->where('locale', 'uk|ru|en')
            ->name('category.homepage');
    });

// Routes shared by the main and category domains.
Route::domain('{domain}')
    ->whereIn('domain', $domains)
    ->middleware([DetectLocale::class,])
    ->group(function () use ($locales){

        //============= SEO ===========================================
        // Dynamic fallbacks: Caddy serves /sites/{host}{path}.br when the
        // static variant exists, otherwise the request lands here and the
        // capture middleware persists the response for the next hit.
        Route::middleware([SeoStaticCaptureMiddleware::class,])
            ->group(function () use ($locales) {

            Route::get('/robots.txt', [SeoController::class, 'robots'])
                ->name('seo.robots');

            Route::get('/sitemap.xml', [SeoController::class, 'sitemapIndex'])
                ->name('seo.sitemap.index');

            // Both placeholders are separated by "_" only, so the locale
            // constraint is what keeps the pattern unambiguous.
            Route::get('/sitemaps/sitemap_{locale}_{page}.xml', [SeoController::class, 'sitemap'])
                ->where('locale', $locales)
                ->where('page', '[0-9]+')
                ->name('seo.sitemap');

            Route::get('/sitemaps/news_{locale}.xml', [SeoController::class, 'newsSitemap'])
                ->where('locale', $locales)
                ->name('seo.sitemap.news');
        });

        //============= PUBLIC PAGES ===================================
        Route::prefix('{locale}')
            ->where(['locale' => $locales])
            ->name('locale.')
            ->group(function () {

                Route::get('/page/{role}', [PageController::class, 'show'])
                    ->where('role', implode('|', PageRoles::all()))
                    ->name('page.role')
                    ->defaults('role', PageRoles::TERMS);

                Route::middleware([StaticCaptureMiddleware::class,])
                    ->group(function () {

                    Route::get('{type}/{slug}-{id}.html', [ArticleController::class, 'showCommon'])
                        ->where('type', 'video|news|article|interview|opinion|person|company|press_rls|infographics')
                        ->name('front.article.show');

                    Route::get('{type}/{subcategory}/{slug}-{id}.html', [ArticleController::class, 'showCommon'])
                        ->where('type', 'video|news|article|interview|opinion')
                        ->name('front.article.show.with.subcategory');
                });
        });
    });

// OLD ROUTES
foreach (['', 'ua'] as $prefix) {

    Route::middleware(DetectLocaleOld::class)
        ->prefix($prefix)
        ->name($prefix ? $prefix . '.' : '')
        ->group(function () {

            Route::get('editors', [LegacyContributorRedirectController::class, 'editors'])
                ->name('old.editors');

            Route::get('editor/{id}-{slug?}', [LegacyContributorRedirectController::class, 'editor'])
                ->where('slug',  '[A-Za-z0-9\-]*')  // important  conditionally
                ->name('old.editor');

            Route::get('author/{id}-{slug?}.html', [LegacyContributorRedirectController::class, 'author'])
                ->where('slug',  '[A-Za-z0-9\-]*')  // important  conditionally
                ->name('old.author');

            Route::get('dossier', [LegacyDossierRedirectController::class, 'dossier'])
                ->name('old.dossier');

            Route::get('dossier/item/{type}/{id}-{slug}.html', [LegacyDossierRedirectController::class, 'show'])
                ->where(['type' => 'person|company']);

            Route::get('/opinions', [LegacyArticleRedirectController::class, 'typeList'])
                ->defaults('type', ArticleType::OPINION)
                ->name('old.opinions');

            Route::get('/article', [LegacyArticleRedirectController::class, 'typeList'])
                ->defaults('type', ArticleType::ARTICLE)
                ->name('old.article');

            Route::get('/interview', [LegacyArticleRedirectController::class, 'typeList'])
                ->defaults('type', ArticleType::INTERVIEW)
                ->name('old.interview');

            Route::get('/news', [LegacyArticleRedirectController::class, 'typeList'])
                ->defaults('type', ArticleType::NEWS)
                ->name('old.news');

            Route::get('/press_rls', [LegacyArticleRedirectController::class, 'typeList'])
                ->defaults('type', ArticleType::PRESS_RLS)
                ->name('old.press_rls');

            Route::get('/infographics', [LegacyArticleRedirectController::class, 'typeList'])
                ->defaults('type', ArticleType::INFOGRAPHICS)
                ->name('old.infographics');

            Route::get('opinion/{id}-{slug}.html', [LegacyArticleRedirectController::class, 'article'])
                ->defaults('type', ArticleType::OPINION);

            Route::get('opinionto/{id}-{slug}.html', [LegacyArticleRedirectController::class, 'article'])
                ->defaults('type', ArticleType::OPINIONTO);

            Route::middleware([StaticCaptureMiddleware::class,])
                ->group(function () {

                    Route::get('{type}/{slug}-{id}.html', [LegacyArticleRedirectController::class, 'article'])
                        ->where(['type' => 'news|article|interview|person|company|press_rls|infographics']);

                    Route::get('{type}/{subcategory}/{slug}-{id}.html', [LegacyArticleRedirectController::class, 'article'])
                        ->where(['type' => 'news|article|interview']);

                    /** for old special links only */
                    Route::get('{type}/{category}/{subcategory}/{id}-{slug}.html', [LegacyArticleRedirectController::class, 'article'])
                        ->where(['type' => 'news|article|interview']);

            });
    });
}

Route::prefix('auth')
    ->middleware($bpBaseWebMiddleware)
    ->group(function () use ($bpAuthMiddleware) {
        Route::get('login', fn() => redirect()->to(AuthUrls::frontendAuth('login')));
        Route::get('register', fn() => redirect()->to(AuthUrls::frontendAuth('registration')));

        Route::post('login', [LoginController::class, 'login'])
            ->middleware(ValidateTurnstile::class)
            ->name('frontend.auth.login');

        Route::post('register', [RegisterController::class, 'register'])
            ->middleware(ValidateTurnstile::class)
            ->name('frontend.auth.register');

        require __DIR__.'/auth_verification.php';

        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('backpack.auth.password.email')
            ->middleware('throttle:'.config('backpack.base.password_recovery_throttle_access'));

        Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
            ->name('backpack.auth.password.reset.token');

        Route::post('password/reset', [ResetPasswordController::class, 'reset'])
            ->name('backpack.auth.password.reset');
    });
