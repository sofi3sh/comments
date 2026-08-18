<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Support\AuthUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse
    {
        $guard = config('backpack.base.guard');
        $user = $request->user($guard);

        if (! $user) {
            return redirect()->guest(AuthUrls::frontendAuth('login'));
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->to(AuthUrls::admin('dashboard'));
        }

        $request->session()->flash('auth_popup_mode', 'verify-email');

        return redirect()->to(AuthUrls::frontend());
    }

    public function send(Request $request): RedirectResponse
    {
        $guard = config('backpack.base.guard');
        $user = $request->user($guard);

        if (! $user) {
            return redirect()->guest(AuthUrls::frontendAuth('login'));
        }

        $user->sendEmailVerificationNotification();

        return back()
            ->with('message', __('auth.verification.sent'))
            ->with('auth_popup_mode', 'verify-email');
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, __('auth.verification.invalid_hash'));
        }

        Auth::guard(config('backpack.base.guard'))->login($user);

        if ($user->hasVerifiedEmail()) {
            return redirect()->to(AuthUrls::admin('dashboard'))
                ->with('success', __('auth.verification.already_verified'));
        }

        $user->markEmailAsVerified();

        return redirect()->to(AuthUrls::admin('dashboard'))
            ->with('success', __('auth.verification.verified'));
    }
}
