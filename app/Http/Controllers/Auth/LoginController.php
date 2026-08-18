<?php

namespace App\Http\Controllers\Auth;

use App\Support\AuthUrls;
use Backpack\CRUD\app\Http\Controllers\Auth\LoginController as BackpackLoginController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends BackpackLoginController
{
    protected function authenticated(Request $request, $user)
    {
        if ($request->routeIs('frontend.auth.login')) {
            return redirect()->to(AuthUrls::admin('dashboard'));
        }

        return null;
    }


    protected function loggedOut(Request $request): RedirectResponse
    {
        return redirect()->to(AuthUrls::frontend());
    }


    protected function sendFailedLoginResponse(Request $request): RedirectResponse
    {
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                $this->username() => trans('auth.failed'),
            ])
            ->with('auth_popup_mode', 'login');
    }
}
