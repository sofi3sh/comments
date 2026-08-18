@php
    $authPopupMode = session('auth_popup_mode');
    $requestAuthMode = request('auth');
    $hasResetToken = old('token') || session('password_reset_token');
    $hasLoginErrors = ! $authPopupMode && ! $requestAuthMode && ! $hasResetToken && ($errors->has('email') || $errors->has('password'));
    $popupMode = $authPopupMode ?: ($requestAuthMode ?: ($hasResetToken ? 'set-password' : null));
@endphp
<div class="authorization {{ ($popupMode || $hasLoginErrors) ? 'authorization--visible' : 'authorization--hide' }}"
     @if($popupMode || $hasLoginErrors) data-mode="{{ $popupMode ?: 'login' }}" @endif>
    <div class="authorization__background"></div>

    <div class="authorization__content">
        <button class="authorization__close">
            <x-icons.cross />
        </button>

        <x-authorizations.login-component />
        <x-authorizations.registration-component />
        <x-authorizations.reset-password-component />
        <x-authorizations.verification-component />
    </div>
</div>
