<div class="registration authorization__wrapper">
    <strong class="authorization__title">{{ __('authorization.registration-title') }}</strong>

    <form class="authorization__form" method="POST" action="{{ route('frontend.auth.register') }}" novalidate data-auth-validation>
        @csrf
        @error('cf-turnstile-response')
        <p class="authorization__error">{{ $message }}</p>
        @enderror
        <div class="form-group-flex">
            <div class="form-group">
                <label class="form-label" for="name">{{ __('authorization.form-name') }} <span class="form-label-required">*</span></label>
                <input class="form-input" type="text" id="name" name="name" value="{{ old('name') }}" placeholder="{{ __('authorization.form-name') }}" autocomplete="name" required maxlength="255" data-required-message="{{ __('authorization.validation-required') }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="email">{{ __('authorization.form-email') }} <span class="form-label-required">*</span></label>
                <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="{{ __('authorization.form-email') }}" autocomplete="email" required maxlength="255" data-required-message="{{ __('authorization.validation-required') }}" data-email-message="{{ __('authorization.validation-email') }}">
            </div>
        </div>
        <div class="form-group-flex">
            <div class="form-group">
                <label class="form-label" for="password">{{ __('authorization.form-password') }} <span class="form-label-required">*</span></label>
                <input class="form-input" type="password" id="password" name="password" autocomplete="new-password" placeholder="{{ __('authorization.form-password') }}" required minlength="8" data-required-message="{{ __('authorization.validation-required') }}" data-minlength-message="{{ __('authorization.validation-password-min') }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">{{ __('authorization.form-password-confirmation') }} <span class="form-label-required">*</span></label>
                <input class="form-input" type="password" id="password_confirmation" name="password_confirmation" placeholder="{{ __('authorization.form-password-confirmation') }}" autocomplete="new-password" required minlength="8" data-required-message="{{ __('authorization.validation-required') }}" data-minlength-message="{{ __('authorization.validation-password-min') }}" data-confirmed-message="{{ __('authorization.validation-password-confirmed') }}">
            </div>
        </div>
        <div class="authorization__form-checkboxes">
            <div class="form-group form-group--checkbox">
                <label class="form-label">
                    <input type="checkbox" id="agree_privacy" name="site_rules_accepted" value="1" class="form-checkbox-input" required data-required-message="{{ __('authorization.validation-terms') }}" {{ old('site_rules_accepted') ? 'checked' : '' }}>
                    <span class="form-checkbox-text">
                    <a href="{{ page_article_url('Політика конфіденційності') }}" target="_blank" rel="noopener noreferrer">
                        {{ __('authorization.label-privacy-policy') }}
                    </a>
                    </span>
                </label>
                @error('site_rules_accepted')
                    <p class="authorization__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group form-group--checkbox">
                <label class="form-label">
                    <input type="checkbox" id="agree_personal_data" name="personal_data_processed" value="1" class="form-checkbox-input" required data-required-message="{{ __('authorization.validation-terms') }}" {{ old('personal_data_processed') ? 'checked' : '' }}>
                    <span class="form-checkbox-text">
                        {{ __('authorization.label-personal-data-consent') }}
                    </span>
                </label>
                @error('personal_data_processed')
                    <p class="authorization__error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-group-flex">
            @if(config('turnstile.enabled') && config('turnstile.site_key'))
                <div class="form-group cf-turnstile-wrapper">
                    <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}" data-theme="light" data-size="normal"></div>
                </div>
            @endif

            <div class="authorization__social">
                <span class="authorization__social-label">{{ __('authorization.social-register-label') }}</span>
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
            <input class="form-input-submit" type="submit" value="{{ __('authorization.form-register') }}" disabled>
        </div>
    </form>

    <div class="authorization__actions">
        <p class="authorization__login">{{ __('authorization.label-have-account') }} <span class="authorization__login-link">{{ __('authorization.form-login') }}</span></p>
    </div>
</div>
