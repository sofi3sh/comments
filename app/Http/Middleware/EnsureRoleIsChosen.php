<?php

namespace App\Http\Middleware;

use App\Support\AuthUrls;
use Closure;
use Illuminate\Http\Request;

class EnsureRoleIsChosen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = backpack_user();
        
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('Customer') && !session()->has('dashboard_role_chosen')) {
            $allowedRoutes = [
                'backpack.dashboard',
                'backpack.choose_role',
                'backpack.auth.logout',
                'set_locale'
            ];
            
            $routeName = $request->route() ? $request->route()->getName() : null;
            
            if ($routeName && !in_array($routeName, $allowedRoutes)) {
                \Alert::error(__('admin.dashboard.must_choose_role'))->flash();
                return redirect()->to(AuthUrls::admin('dashboard'));
            }
        }
        
        return $next($request);
    }
}
