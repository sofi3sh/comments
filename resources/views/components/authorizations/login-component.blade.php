<div class="login authorization__wrapper">
    <strong class="authorization__title">{{ __('authorization.login-title') }}</strong>

    <form class="authorization__form" method="POST" action="{{ route('frontend.auth.login') }}" novalidate data-auth-validation>
        @csrf
        @error('cf-turnstile-response')
            <p class="authorization__error">{{ $message }}</p>
        @enderror
        <div class="form-group">
            <label class="form-label" for="email">{{ __('authorization.form-email') }} <span class="form-label-required">*</span></label>
            <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="{{ __('authorization.form-email') }}" autocomplete="email" required data-required-message="{{ __('authorization.validation-required') }}" data-email-message="{{ __('authorization.validation-email') }}">
            @error('email')
                <p class="authorization__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="password">{{ __('authorization.form-password') }} <span class="form-label-required">*</span></label>
            <input class="form-input" type="password" id="password" name="password" placeholder="{{ __('authorization.form-password') }}" autocomplete="current-password" required data-required-message="{{ __('authorization.validation-required') }}">
            @error('password')
                <p class="authorization__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-group-checkbox">
            <input class="form-input-checkbox" type="checkbox" id="remember" name="remember">
            <label class="form-label" for="remember">{{ __('authorization.form-remember-me') }}</label>
        </div>

        <div class="form-group-flex">
            @if(config('turnstile.enabled') && config('turnstile.site_key'))
                <div class="form-group cf-turnstile-wrapper">
                    <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}" data-theme="light" data-size="normal"></div>
                </div>
            @endif

            <div class="authorization__social">
                <span class="authorization__social-label">{{ __('authorization.social-login-label') }}</span>
                <div class="authorization__social-links">
                    <a class="authorization__social-link authorization__social-link--google" href="{{ route('social.redirect', ['provider' => 'google']) }}">
                        {{ __('authorization.social-login-google') }}
                    </a>
                    <a class="authorization__social-link authorization__social-link--facebook" href="{{ route('social.redirect', ['provider' => 'facebook']) }}">
                        {{ __('authorization.social-login-facebook') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="form-group">
            <input class="form-input-submit" type="submit" value="{{ __('authorization.form-login') }}" disabled>
        </div>
    </form>

    <div class="authorization__actions">
        <p class="authorization__register">{{ __('authorization.label-dont-have-account') }} <span class="authorization__register-link">{{ __('authorization.form-register') }}</span></p>
        <span class="authorization__forgot-password">{{ __('authorization.label-forgot-password') }}</span>
    </div>

</div>
