document.addEventListener('DOMContentLoaded', function() {
    const authorizationPopup = document.querySelector('.authorization');
    const closeButton = document.querySelector('.authorization__close');
    const openButton = document.querySelector('.header__actions-auth:not(.header__actions-auth--logout)');
    const burgerSignInButton = document.querySelector('.burger__sign-in');
    const loginWrapper = document.querySelector('.login.authorization__wrapper');
    const registrationWrapper = document.querySelector('.registration.authorization__wrapper');
    const resetPasswordWrapper = document.querySelector('.reset-password.authorization__wrapper');
    const verificationWrapper = document.querySelector('.verification.authorization__wrapper');
    const registerLink = document.querySelector('.authorization__register-link');
    const forgotPasswordLink = document.querySelector('.authorization__forgot-password');
    const loginLinks = document.querySelectorAll('.authorization__login-link');

    if (!authorizationPopup) {
        return;
    }

    let turnstilePromise = null;

    function isPopupVisible() {
        return authorizationPopup.classList.contains('authorization--visible');
    }

    function loadTurnstile() {
        if (window.turnstile) {
            return Promise.resolve(window.turnstile);
        }

        if (!turnstilePromise) {
            turnstilePromise = new Promise(function(resolve, reject) {
                const script = document.createElement('script');
                script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
                script.async = true;
                script.defer = true;
                script.dataset.turnstileScript = 'true';
                script.onload = function() {
                    if (window.turnstile) {
                        resolve(window.turnstile);
                    } else {
                        turnstilePromise = null;
                        reject(new Error('Turnstile API did not initialize.'));
                    }
                };
                script.onerror = function() {
                    turnstilePromise = null;
                    reject(new Error('Failed to load Turnstile API script.'));
                };
                document.head.appendChild(script);
            });
        }

        return turnstilePromise;
    }

    function renderTurnstileIn(container) {
        if (!container || !isPopupVisible()) {
            return;
        }

        const widgets = container.querySelectorAll('.cf-turnstile:not([data-turnstile-rendered])');
        if (!widgets.length) {
            return;
        }

        loadTurnstile()
            .then(function(turnstile) {
                widgets.forEach(function(widget) {
                    if (widget.dataset.turnstileRendered) {
                        return;
                    }

                    turnstile.render(widget, {
                        sitekey: widget.dataset.sitekey,
                        theme: widget.dataset.theme || 'light',
                        size: widget.dataset.size || 'normal'
                    });
                    widget.dataset.turnstileRendered = 'true';
                });
            })
            .catch(function(error) {
                console.error('Failed to load Turnstile.', error);
            });
    }

    function hideAllForms() {
        if (loginWrapper) {
            loginWrapper.classList.remove('authorization__content--show');
            loginWrapper.classList.add('authorization__content--hide');
        }
        if (registrationWrapper) {
            registrationWrapper.classList.remove('authorization__content--show');
            registrationWrapper.classList.add('authorization__content--hide');
        }
        if (resetPasswordWrapper) {
            resetPasswordWrapper.classList.remove('authorization__content--show');
            resetPasswordWrapper.classList.add('authorization__content--hide');
        }
        if (verificationWrapper) {
            verificationWrapper.classList.remove('authorization__content--show');
            verificationWrapper.classList.add('authorization__content--hide');
        }
    }

    function showLogin() {
        hideAllForms();
        if (loginWrapper) {
            loginWrapper.classList.remove('authorization__content--hide');
            loginWrapper.classList.add('authorization__content--show');
            renderTurnstileIn(loginWrapper);
        }
    }

    function showRegistration() {
        hideAllForms();
        if (registrationWrapper) {
            registrationWrapper.classList.remove('authorization__content--hide');
            registrationWrapper.classList.add('authorization__content--show');
            renderTurnstileIn(registrationWrapper);
        }
    }

    function showResetPassword() {
        hideAllForms();
        if (resetPasswordWrapper) {
            resetPasswordWrapper.classList.remove('authorization__content--hide');
            resetPasswordWrapper.classList.add('authorization__content--show');
        }
    }

    function showVerification() {
        hideAllForms();
        if (verificationWrapper) {
            verificationWrapper.classList.remove('authorization__content--hide');
            verificationWrapper.classList.add('authorization__content--show');
        }
    }

    function initForms() {
        hideAllForms();
        const params = new URLSearchParams(window.location.search);
        const mode = params.get('auth') || authorizationPopup.dataset.mode;

        if (params.has('auth')) {
            authorizationPopup.classList.remove('authorization--hide');
            authorizationPopup.classList.add('authorization--visible');
            document.body.style.overflow = 'hidden';
        }

        if (mode === 'verify-email' && verificationWrapper) {
            showVerification();
        } else if ((mode === 'reset-password' || mode === 'set-password') && resetPasswordWrapper) {
            showResetPassword();
        } else if (mode === 'registration' && registrationWrapper) {
            showRegistration();
        } else {
            showLogin();
        }
    }

    function closePopup() {
        authorizationPopup.classList.remove('authorization--visible');
        authorizationPopup.classList.add('authorization--hide');
        document.body.style.overflow = '';
        showLogin();
    }

    function openPopup(mode) {
        authorizationPopup.classList.remove('authorization--hide');
        authorizationPopup.classList.add('authorization--visible');
        document.body.style.overflow = 'hidden';
        if (mode === 'verify-email' && verificationWrapper) {
            showVerification();
        } else if ((mode === 'reset-password' || mode === 'set-password') && resetPasswordWrapper) {
            showResetPassword();
        } else if (mode === 'registration' && registrationWrapper) {
            showRegistration();
        } else {
            showLogin();
        }
    }

    initForms();

    if (authorizationPopup && authorizationPopup.classList.contains('authorization--visible')) {
        document.body.style.overflow = 'hidden';
    }

    if (closeButton) {
        closeButton.addEventListener('click', closePopup);
    }

    if (openButton) {
        openButton.addEventListener('click', function(e) {
            e.preventDefault();
            openPopup();
        });
    }

    if (burgerSignInButton) {
        burgerSignInButton.addEventListener('click', function(e) {
            e.preventDefault();
            openPopup();
        });
    }

    if (registerLink) {
        registerLink.addEventListener('click', function(e) {
            e.preventDefault();
            showRegistration();
        });
    }

    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            showResetPassword();
        });
    }

    loginLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showLogin();
        });
    });

    function getFieldErrorElement(field) {
        const formGroup = field.closest('.form-group') || field.parentElement;
        if (!formGroup) {
            return null;
        }

        let errorElement = formGroup.querySelector('.authorization__error[data-client-error]');
        if (!errorElement) {
            errorElement = document.createElement('p');
            errorElement.className = 'authorization__error';
            errorElement.dataset.clientError = 'true';
            formGroup.appendChild(errorElement);
        }

        return errorElement;
    }

    function setFieldError(field, message) {
        const errorElement = getFieldErrorElement(field);
        if (errorElement) {
            errorElement.textContent = message;
        }
        field.classList.add('form-input--invalid');
    }

    function clearFieldError(field) {
        const formGroup = field.closest('.form-group') || field.parentElement;
        const errorElement = formGroup ? formGroup.querySelector('.authorization__error[data-client-error]') : null;
        if (errorElement) {
            errorElement.remove();
        }
        field.classList.remove('form-input--invalid');
    }

    function clearAllFieldErrors(field) {
        const formGroup = field.closest('.form-group') || field.parentElement;
        if (!formGroup) {
            return;
        }

        formGroup.querySelectorAll('.authorization__error').forEach(function(errorElement) {
            errorElement.remove();
        });
        field.classList.remove('form-input--invalid');
    }

    function validateField(field, form) {
        clearFieldError(field);

        if (field.required) {
            const isEmpty = field.type === 'checkbox' ? !field.checked : !field.value.trim();
            if (isEmpty) {
                setFieldError(field, field.dataset.requiredMessage || field.validationMessage);
                return false;
            }
        }

        if (field.type === 'email' && field.value.trim()) {
            const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;
            if (!emailPattern.test(field.value.trim())) {
                setFieldError(field, field.dataset.emailMessage || field.validationMessage);
                return false;
            }
        }

        if (field.minLength > 0 && field.value.length < field.minLength) {
            setFieldError(field, field.dataset.minlengthMessage || field.validationMessage);
            return false;
        }

        if (field.name === 'password_confirmation') {
            const passwordField = form.querySelector('[name="password"]');
            if (passwordField && field.value !== passwordField.value) {
                setFieldError(field, field.dataset.confirmedMessage || field.validationMessage);
                return false;
            }
        }

        return true;
    }

    document.querySelectorAll('[data-auth-validation]').forEach(function(form) {
        const fields = form.querySelectorAll('input[required], input[type="email"], input[minlength], [name="password_confirmation"]');
        const loginEmailField = form.closest('.login') ? form.querySelector('[name="email"]') : null;
        const loginPasswordField = form.closest('.login') ? form.querySelector('[name="password"]') : null;
        const loginSubmitButton = form.closest('.login') ? form.querySelector('.form-input-submit') : null;

        function updateLoginButtonState() {
            if (!loginEmailField || !loginPasswordField || !loginSubmitButton) {
                return;
            }

            loginSubmitButton.disabled = !loginEmailField.value.trim() || !loginPasswordField.value.trim();
        }

        if (loginEmailField) {
            loginEmailField.addEventListener('focus', function() {
                clearAllFieldErrors(loginEmailField);
            });
        }

        fields.forEach(function(field) {
            field.addEventListener('input', function() {
                validateField(field, form);
                updateLoginButtonState();

                if (field.name === 'password') {
                    const confirmationField = form.querySelector('[name="password_confirmation"]');
                    if (confirmationField && confirmationField.value) {
                        validateField(confirmationField, form);
                    }
                }
            });

            field.addEventListener('change', function() {
                validateField(field, form);
                updateLoginButtonState();
            });
        });

        updateLoginButtonState();

        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalidField = null;

            fields.forEach(function(field) {
                if (!validateField(field, form)) {
                    isValid = false;
                    firstInvalidField = firstInvalidField || field;
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
            }
        });
    });

    const registrationForm = document.querySelector('.registration.authorization__wrapper .authorization__form');
    if (registrationForm) {
        const submitButton = registrationForm.querySelector('.form-input-submit');
        const privacyCheckbox = registrationForm.querySelector('#agree_privacy');
        const personalDataCheckbox = registrationForm.querySelector('#agree_personal_data');
        const socialLinks = registrationForm.querySelectorAll('.authorization__social-link');

        if (submitButton && privacyCheckbox && personalDataCheckbox) {
            function updateRegisterButtonState() {
                const isEnabled = privacyCheckbox.checked && personalDataCheckbox.checked;
                submitButton.disabled = !isEnabled;
                socialLinks.forEach(function(link) {
                    if (isEnabled) {
                        link.classList.remove('authorization__social-link--disabled');
                    } else {
                        link.classList.add('authorization__social-link--disabled');
                    }
                });
            }

            updateRegisterButtonState();

            privacyCheckbox.addEventListener('change', updateRegisterButtonState);
            personalDataCheckbox.addEventListener('change', updateRegisterButtonState);
        }
    }

    const background = authorizationPopup.querySelector('.authorization__background');
    if (background) {
        background.addEventListener('click', closePopup);
    }
});
