<?php

namespace App\Http\Controllers\Auth;

use App\Support\AuthUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends \Backpack\CRUD\app\Http\Controllers\Auth\ResetPasswordController
{
    public function showResetForm(Request $request, $token = null)
    {
        return redirect()
            ->to(AuthUrls::frontendAuth('set-password'))
            ->with('auth_popup_mode', 'set-password')
            ->with('password_reset_token', $token)
            ->with('password_reset_email', $request->email);
    }

    public function reset(Request $request)
    {
        try {
            return parent::reset($request);
        } catch (ValidationException $exception) {
            $request->session()->flash('auth_popup_mode', 'set-password');
            $request->session()->flash('password_reset_token', $request->input('token'));
            $request->session()->flash('password_reset_email', $request->input('email'));

            throw $exception;
        }
    }

    protected function sendResetResponse(Request $request, $response): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        }

        return redirect()
            ->to(AuthUrls::admin('dashboard'))
            ->with('status', trans($response));
    }

    protected function sendResetFailedResponse(Request $request, $response): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($response)])
            ->with('auth_popup_mode', 'set-password')
            ->with('password_reset_token', $request->input('token'))
            ->with('password_reset_email', $request->input('email'));
    }

}
