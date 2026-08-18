<?php

use App\Support\AuthUrls;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(

        web: __DIR__.'/../routes/web.php',

        api: __DIR__.'/../routes/api.php',

        commands: __DIR__.'/../routes/console.php',

        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) : void {

        $middleware->redirectGuestsTo(fn(\Illuminate\Http\Request $request) =>
            AuthUrls::frontendAuth('login')
        );

        $middleware->trustProxies(at: '*');

        $middleware->web(prepend: [
            \App\Http\Middleware\SetLastModified::class,
        ]);

        // ResolveSite skips the admin host — see the middleware itself.
        // SetLocale is not listed here: the admin panel gets it via
        // config/backpack/base.php `middleware_class`, and its frontend
        // branch is not enabled yet.
        $middleware->web(append: [
            \App\Http\Middleware\InjectGlobalSchemas::class,
            \App\Http\Middleware\ResolveSite::class,
        ]);

        $middleware->alias([
            'static.page' => \App\Http\Middleware\StaticCaptureMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
