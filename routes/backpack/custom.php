<?php

use App\Http\Controllers\Admin\Articles\ArticleFieldConfigurationCrudController;
use App\Http\Controllers\Admin\Audit\AuditController;
use App\Http\Controllers\Admin\Articles\ArticleAutoTranslateController;
use App\Http\Controllers\Admin\Articles\ArticleContentUniquenessController;
use App\Http\Controllers\Admin\Articles\ArticleImportController;
use App\Http\Controllers\Admin\Articles\ArticlesBlockSettingsCrudController;
use App\Http\Controllers\Admin\Articles\ArticleTranslationPermissionCrudController;
use App\Http\Controllers\Admin\Articles\ArticleTypeCrudController;
use App\Http\Controllers\Admin\Articles\ArticleCrudController;
use App\Http\Controllers\Admin\Articles\AttachmentCrudController;
use App\Http\Controllers\Admin\Articles\CategoryCrudController;
use App\Http\Controllers\Admin\Articles\MarkerCrudController;
use App\Http\Controllers\Admin\Articles\PublicationHistoryCrudController;
use App\Http\Controllers\Admin\Articles\TagCrudController;
use App\Http\Controllers\Admin\Settings\LocaleCrudController;
use App\Http\Controllers\Admin\Settings\SettingCrudController;
use App\Http\Controllers\Admin\Site\SiteCrudController;
use App\Http\Controllers\Admin\StaticCache\ManualStaticPublicInvalidationController;
use App\Http\Controllers\Admin\User\MyAccountController;
use App\Http\Controllers\Admin\User\PermissionCrudController;
use App\Http\Controllers\Admin\User\RoleCrudController;
use App\Http\Controllers\Admin\User\UserApplicationCrudController;
use App\Http\Controllers\Admin\User\UserCrudController;
use App\Http\Controllers\Auth\LoginController;
use App\Support\AuthUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$bpRouteBasePrefix = config('backpack.base.route_prefix', 'admin');
$bpBaseWebMiddleware = config('backpack.base.web_middleware', 'web');
$bpAuthMiddleware = 'auth:'.config('backpack.base.guard');

Route::group([
    'prefix' => $bpRouteBasePrefix,
    'middleware' => array_merge(
        (array) $bpBaseWebMiddleware,
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () {

    Route::get('choose-role', function () {
        return redirect()->to(AuthUrls::admin('dashboard'));
    });

    Route::post('choose-role', function(Request $request) {
        $user = backpack_user();
        if ($user && $user->hasRole('Customer')) {
            $choice = $request->input('role');

            if ($choice === 'user') {
                session()->put('dashboard_role_chosen', true);
                \Alert::success(__('admin.dashboard.role_success', ['role' => __('admin.dashboard.role_user')]))->flash();
                return redirect()->route('backpack.account.info');
            }

            if ($choice === 'blogger') {
                session()->put('dashboard_role_chosen', 'blogger_application');
                return redirect()->route('backpack.account.info');
            }

            if ($choice === 'company') {
                session()->put('dashboard_role_chosen', 'company_application');
                return redirect()->route('backpack.account.info');
            }
        }
        return redirect()->back();
    })->name('backpack.choose_role');

    Route::post('set-locale', function () {
        $locale = request('locale');
        $allowedLocales = ['en', 'ru', 'uk'];//TODO

        if (in_array($locale, $allowedLocales)) {
            session(['locale' => $locale]);
        }

        return back();
    })->name('set_locale');

    Route::get('edit-account-info', [MyAccountController::class, 'getAccountInfoForm'])->name('backpack.account.info');
    Route::get('companies/search', [MyAccountController::class, 'searchCompanies'])->name('companies.search');

    // Fetch children categories
    Route::get('category/fetch-children', [CategoryCrudController::class, 'fetchChildren'])->name('category.fetchChildren');
    Route::post('edit-account-info', [MyAccountController::class, 'postAccountInfoForm'])->name('backpack.account.info.store');
    Route::get('static-cache/manual-public', [ManualStaticPublicInvalidationController::class, 'index'])
        ->name('static-cache.manual-public.index');
    Route::post('static-cache/manual-public', [ManualStaticPublicInvalidationController::class, 'store'])
        ->name('static-cache.manual-public.store');

    Route::crud('user', UserCrudController::class);
    Route::post('user/{id}/block', [UserCrudController::class, 'block'])
        ->whereNumber('id')
        ->name('user.block');
    Route::post('user/{id}/restore', [UserCrudController::class, 'restore'])
        ->whereNumber('id')
        ->name('user.restore');
    Route::crud('user-application', UserApplicationCrudController::class);
    Route::post('user-application/{id}/approve', [UserApplicationCrudController::class, 'approve'])->name('user-application.approve');
    Route::post('user-application/{id}/reject', [UserApplicationCrudController::class, 'reject'])->name('user-application.reject');
    Route::crud('role', RoleCrudController::class);
    Route::get('permission', [PermissionCrudController::class, 'index'])->name('permission.index');
    Route::post('permission/assign', [PermissionCrudController::class, 'storeAssignments'])->name('permission.assign');
    Route::crud('site', SiteCrudController::class);
    Route::crud('category', CategoryCrudController::class);
    Route::crud('settings', SettingCrudController::class);
    Route::crud('publication-history', PublicationHistoryCrudController::class);
    Route::get('audits', [AuditController::class, 'index'])->name('audit.index');
    Route::get('audits/{id}', [AuditController::class, 'show'])
        ->whereNumber('id')
        ->name('audit.show');

    // Article import routes  OFF
//    Route::get('article/import', [ArticleImportController::class, 'showImportForm'])->name('article.import.form');
//    Route::post('article/import', [ArticleImportController::class, 'processImport'])->name('article.import.process');

//    Route::crud('article', ArticleCrudController::class);

    Route::group([
        'prefix' => 'article/{type}',
        'as' => 'article',
    ], function () {
        Route::get('deleted', [
            'uses' => ArticleCrudController::class.'@index',
            'operation' => 'list',
        ])
            ->name('.deleted');

        Route::post('deleted/search', [
            'uses' => ArticleCrudController::class.'@search',
            'operation' => 'list',
        ])
            ->name('.deleted.search');

        Route::post('auto-translate', ArticleAutoTranslateController::class)
            ->name('.auto-translate');

        Route::post('reserve', [ArticleCrudController::class, 'reserve'])
            ->name('.reserve');

        Route::post('bulk-delete', [ArticleCrudController::class, 'bulkDelete'])
            ->name('.bulk-delete');

        Route::post('{id}/restore', [ArticleCrudController::class, 'restore'])
            ->whereNumber('id')
            ->name('.restore');

        Route::post('{id}/invalidate-static', [ArticleCrudController::class, 'invalidateStatic'])
            ->whereNumber('id')
            ->name('.invalidate-static');

        Route::get('{id}/content-uniqueness/{locale}', [ArticleContentUniquenessController::class, 'show'])
            ->whereNumber('id')
            ->name('.content-uniqueness.show');

        Route::post('{id}/content-uniqueness/{locale}/recheck', [ArticleContentUniquenessController::class, 'recheck'])
            ->whereNumber('id')
            ->name('.content-uniqueness.recheck');

        Route::crud('', ArticleCrudController::class);
    });

    Route::crud('article-type', ArticleTypeCrudController::class);
    Route::crud('article-field-configuration', ArticleFieldConfigurationCrudController::class);
    Route::crud('articles-block-settings', ArticlesBlockSettingsCrudController::class);
    Route::crud('article-translation-permission', ArticleTranslationPermissionCrudController::class);
    Route::crud('tag', TagCrudController::class);
    Route::crud('marker', MarkerCrudController::class);
    Route::crud('locale', LocaleCrudController::class);
    Route::crud('attachment', AttachmentCrudController::class);

    // Test route for logging
    Route::get('article/import/test', function() {
        $logFile = storage_path('logs/import_debug.log');
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " === TEST ROUTE CALLED ===\n", FILE_APPEND | LOCK_EX);
        @error_log('=== TEST ROUTE CALLED ===');
        return response()->json([
            'status' => 'ok',
            'log_file' => $logFile,
            'log_file_exists' => file_exists($logFile),
            'log_file_writable' => is_writable($logFile) || (!file_exists($logFile) && is_writable(dirname($logFile))),
            'logs_dir_writable' => is_writable(storage_path('logs')),
        ]);
    })->name('article.import.test');

});

Route::group([
    'prefix'     => $bpRouteBasePrefix,
    'middleware' => $bpBaseWebMiddleware,
], function () use ($bpAuthMiddleware) {
    Route::get('login', fn() => redirect()->to(AuthUrls::frontendAuth('login')))
        ->name('backpack.auth.login');

    Route::get('logout', [LoginController::class, 'logout'])->name('backpack.auth.logout');
    Route::post('logout', [LoginController::class, 'logout']);

    require __DIR__.'/../auth_verification.php';
});
