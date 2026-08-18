<?php

namespace App\Http\Middleware;

use App\Helpers\LocaleUrlHelper;
use App\Models\Settings\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     * NOW OFF -> Фронт: локаль з URL (префікс /ru/, /en/) або мова за замовчуванням.
     * Адмінка: локаль з сесії (перемикач у Backpack) або мова за замовчуванням.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAdminRequest($request)) {
            $this->setLocaleForAdmin();

            return $next($request);
        }

//        $this->setLocaleForFrontend($request);  //todo

        return $next($request);
    }

    /**
     * Адмінка: спочатку сесія (вибір перемикача), інакше — мова за замовчуванням.
     */
    protected function setLocaleForAdmin(): void
    {
        if (session()->has('locale')) {
            $sessionLocale = Locale::where('code', session('locale'))->active()->first();
            if ($sessionLocale) {
                app()->setLocale($sessionLocale->code);

                return;
            }
        }

        $defaultLocale = Locale::getDefault();
        if ($defaultLocale && $defaultLocale->is_active) {
            app()->setLocale($defaultLocale->code);
            session(['locale' => $defaultLocale->code]);
        }
    }


    protected function setLocaleForFrontend(Request $request): void
    {
        $routeLocale = $request->route('locale');

        if ($routeLocale !== null && ! LocaleUrlHelper::isLocalePrefix($routeLocale)) {
            abort(404);
        }

        $localeCode = $routeLocale ?? Locale::getDefault()?->code ?? config('app.fallback_locale', 'uk');
        app()->setLocale($localeCode);

        if ($routeLocale !== null) {
            URL::defaults(['locale' => $routeLocale]);
        } else {
            URL::defaults(['locale' => null]);
        }
    }

    protected function isAdminRequest(Request $request): bool
    {
        $prefix = config('backpack.base.route_prefix', 'admin');

        if ($prefix !== '') {
            return $request->is($prefix) || $request->is($prefix.'/*');
        }

        $route = $request->route();
        if ($route === null) {
            return false;
        }

        $adminMiddleware = config('backpack.base.middleware_key', 'admin');

        return in_array($adminMiddleware, $route->gatherMiddleware(), true)
            || $request->routeIs('backpack.*')
            || $request->routeIs('set_locale');
    }
}
