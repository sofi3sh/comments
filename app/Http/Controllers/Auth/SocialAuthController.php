<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialAuthController extends Controller
{
    protected array $supportedProviders = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureProviderSupported($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureProviderSupported($provider);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()
                ->route('homepage')
                ->with('error', __('authorization.social-login-failed'));
        }

        $providerUserId = (string) $socialUser->getId();
        $email = $socialUser->getEmail();

        if (empty($providerUserId) || empty($email)) {
            return redirect()
                ->route('homepage')
                ->with('error', __('authorization.social-login-missing-email'));
        }

        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        $userModelClass = config('backpack.base.user_model_fqn');

        if ($socialAccount) {
            $user = $socialAccount->user;
        } else {
            /** @var \Illuminate\Database\Eloquent\Model $userModelClass */
            $user = $userModelClass::where(config('backpack.base.authentication_column'), $email)->first();

            if (! $user) {
                $user = $userModelClass::create([
                    'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $email,
                    config('backpack.base.email_column') => $email,
                    'password' => Hash::make(Str::random(32)),
                ]);

                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();

                if (class_exists(Role::class)) {
                    try {
                        $user->assignRole(Role::findByName('Customer', 'web'));
                    } catch (\Throwable $e) {
                        // Ignore if role does not exist or cannot be assigned.
                    }
                }
            } elseif (is_null($user->email_verified_at)) {
                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();
            }

            SocialAccount::create([
                'user_id' => $user->getKey(),
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ]);
        }

        backpack_auth()->login($user);

        return redirect()->intended(backpack_url('dashboard'));
    }

    protected function ensureProviderSupported(string $provider): void
    {
        if (! in_array($provider, $this->supportedProviders, true)) {
            abort(404);
        }
    }
}

