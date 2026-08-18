@php
    $isSetPasswordMode = session('auth_popup_mode') === 'set-password'
        || request('auth') === 'set-password'
        || old('token')
        || session('password_reset_token');
    $resetToken = old('token', session('password_reset_token'));
    $resetEmail = old('email', session('password_reset_email'));
@endphp
<div class="reset-password authorization__wrapper">
    <strong class="authorization__title">{{ __('authorization.reset-password-title') }}</strong>

    <form class="authorization__form" method="POST" action="{{ $isSetPasswordMode ? route('backpack.auth.password.reset') : route('backpack.auth.password.email') }}" @if($isSetPasswordMode) data-auth-validation @endif>
        @csrf
        @if($isSetPasswordMode)
            <input type="hidden" name="token" value="{{ $resetToken }}">
        @endif

        <div class="form-group">
            <label class="form-label" for="email">{{ __('authorization.form-email') }} <span class="form-label-required">*</span></label>
            <input class="form-input" type="email" id="email" name="email" value="{{ $isSetPasswordMode ? $resetEmail : old('email') }}" placeholder="{{ __('authorization.form-email') }}" @if($isSetPasswordMode) required data-required-message="{{ __('authorization.validation-required') }}" data-email-message="{{ __('authorization.validation-email') }}" @endif>
        </div>
        @error('email')
            <p class="authorization__error">{{ $message }}</p>
        @enderror

        @if($isSetPasswordMode)
            <div class="form-group">
                <label class="form-label" for="reset_password">{{ __('authorization.form-password') }} <span class="form-label-required">*</span></label>
                <input class="form-input" type="password" id="reset_password" name="password" placeholder="{{ __('authorization.form-password') }}" required minlength="8" data-required-message="{{ __('authorization.validation-required') }}" data-minlength-message="{{ __('authorization.validation-password-min') }}">
            </div>
            @error('password')
                <p class="authorization__error">{{ $message }}</p>
            @enderror

            <div class="form-group">
                <label class="form-label" for="reset_password_confirmation">{{ __('authorization.form-password-confirmation') }} <span class="form-label-required">*</span></label>
                <input class="form-input" type="password" id="reset_password_confirmation" name="password_confirmation" placeholder="{{ __('authorization.form-password-confirmation') }}" required data-required-message="{{ __('authorization.validation-required') }}" data-confirmed-message="{{ __('authorization.validation-password-confirmed') }}">
            </div>
        @endif

        @if (session('status'))
            <p class="authorization__success">{{ session('status') }}</p>
        @endif
        <div class="form-group">
            <input class="form-input-submit" type="submit" value="{{ __('authorization.form-reset-password') }}">
        </div>
    </form>

    <div class="authorization__actions">
        <p class="authorization__login">{{ __('authorization.label-have-account') }} <span class="authorization__login-link">{{ __('authorization.form-login') }}</span></p>
    </div>
</div>
