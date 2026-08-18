<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Traits\ValidatesPhoneNumber;
use App\Http\Requests\User\UserRegisterRequest;
use App\Support\AuthUrls;
use Backpack\CRUD\app\Http\Controllers\Auth\RegisterController as BackpackRegisterController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RegisterController extends BackpackRegisterController
{
    use ValidatesPhoneNumber;

    public function redirectPath(): string
    {
        return AuthUrls::admin('dashboard');
    }

    public function register(Request $request)
    {
        if (! $request->routeIs('frontend.auth.register')) {
            abort(404);
        }

        $registerRequest = app(UserRegisterRequest::class);
        $registerRequest->replace($request->all());
        $registerRequest->setMethod($request->getMethod());
        $registerRequest->setUserResolver($request->getUserResolver());
        $registerRequest->setRouteResolver($request->getRouteResolver());
        $registerRequest->headers->replace($request->headers->all());

        try {
            $validated = $registerRequest->validated();
        } catch (ValidationException $e) {
            session()->flash('auth_popup_mode', 'registration');

            throw $e;
        }

        if (!empty($validated['phone'])) {
            $validated['phone'] = $this->normalizePhoneNumber($validated['phone']);
        }

        $user = $this->create($validated);

        $user->assignRole(Role::findByName('Customer', 'web'));

        event(new Registered($user));

        $this->guard()->login($user);

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            Cookie::queue('backpack_email_verification', $user->{config('backpack.base.email_column')}, 30);

            return redirect(route('verification.notice'));
        }

        return redirect($this->redirectPath());
    }

    protected function create(array $data)
    {
        $user_model_fqn = config('backpack.base.user_model_fqn');
        $user = new $user_model_fqn();

        $user->setRawAttributes([
            'name' => $data['name'],
            backpack_authentication_column() => $data[backpack_authentication_column()],
            'phone' => $data['phone'] ?? null,
            'site_rules_accepted' => ! empty($data['site_rules_accepted']),
            'personal_data_processed' => ! empty($data['personal_data_processed']),
            'password' => Hash::make($data['password']),
        ]);

        $user->save();

        return $user;
    }
}
