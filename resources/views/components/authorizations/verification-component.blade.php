<div class="verification authorization__wrapper authorization__content--hide">
    <strong class="authorization__title">{{ __('auth.verification.title') }}</strong>

    @if (session('message'))
        <p class="authorization__success">{{ session('message') }}</p>
    @endif

    <p class="authorization__text">
        {{ __('auth.verification.notice') }}
    </p>

    <form class="authorization__form" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <div class="form-group">
            <input class="form-input-submit" type="submit" value="{{ __('auth.verification.resend') }}">
        </div>
    </form>

{{--    <div class="authorization__actions">--}}
{{--        <p class="authorization__login">--}}
{{--            {{ __('authorization.label-have-account') }}--}}
{{--            <span class="authorization__login-link">{{ __('authorization.form-login') }}</span>--}}
{{--        </p>--}}
{{--    </div>--}}
</div>

