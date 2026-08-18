<?php

namespace App\Http\Requests\User;

use App\Models\Settings\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Traits\ValidatesPhoneNumber;

abstract class BaseUserRequest extends FormRequest
{
    use ValidatesPhoneNumber;

    protected function nameRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function surnameRules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:255'],
        ];
    }

    protected function socialUrlRules(bool $required = false): array
    {
        $presenceRule = $required ? 'required' : 'nullable';

        return [
            'facebook_url' => [$presenceRule, 'string', 'url', 'max:500'],
            'linkedin_url' => [$presenceRule, 'string', 'url', 'max:500'],
            'twitter_url'  => [$presenceRule, 'string', 'url', 'max:500'],
        ];
    }

    /**
     * @param array<int, string>|null $localeCodes
     * @return array<string, array<int, string>>
     */
    protected function bioTranslationRules(?array $localeCodes = null, bool $required = false): array
    {
        $codes = $localeCodes ?? Locale::getAvailableAsArr('code');
        $rules = [];
        $presenceRule = $required ? 'required' : 'nullable';
        foreach ($codes as $code) {
            $rules['bio_' . $code] = [$presenceRule, 'string', 'max:5000'];
        }
        return $rules;
    }

    /**
     * @param array<int, string>|null $localeCodes
     * @return array<string, array<int, string>>
     */
    protected function nameTranslationRules(?array $localeCodes = null): array
    {
        $codes = $localeCodes ?? Locale::getAvailableAsArr('code');
        $rules = [];
        foreach ($codes as $code) {
            $rules['name_' . $code] = ['nullable', 'string', 'max:255'];
        }
        return $rules;
    }

    /**
     * @param array<int, string>|null $localeCodes
     * @return array<string, array<int, string>>
     */
    protected function surnameTranslationRules(?array $localeCodes = null): array
    {
        $codes = $localeCodes ?? Locale::getAvailableAsArr('code');
        $rules = [];
        foreach ($codes as $code) {
            $rules['surname_' . $code] = ['nullable', 'string', 'max:255'];
        }
        return $rules;
    }

    protected function emailRules(?int $ignoreUserId = null): array
    {
        $userModel = config('backpack.base.user_model_fqn', \App\Models\User\User::class);
        $user = new $userModel();
        $usersTable = $user->getTable();

        $emailValidation = backpack_authentication_column() == 'email' ? 'email' : 'string';

        $rules = [
            'required',
            'string',
            $emailValidation,
            'regex:/^[^@]+@[^@]+\.[^@]+$/',
            'max:255',
        ];

        if ($ignoreUserId !== null) {
            $rules[] = Rule::unique($usersTable, backpack_authentication_column())->ignore($ignoreUserId);
        } else {
            $rules[] = Rule::unique($usersTable, backpack_authentication_column());
        }

        return [
            backpack_authentication_column() => $rules,
        ];
    }

    protected function phoneRules(bool $required = false): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            'regex:/^[\d\s\-+]+$/',
            function ($attribute, $value, $fail) {
                if ($value && !$this->isValidPhoneNumber($value)) {
                    $fail(__('user.validation.phone_format'));
                }
            },
        ];

        return [
            'phone' => $rules,
        ];
    }

    protected function avatarRules(bool $required = false): array
    {
        if ($required) {
            return [
                'avatar' => [
                    'required',
                    'image',
                    'mimes:jpeg,jpg,png,gif',
                    'max:2048',
                ],
            ];
        }

        if ($this->hasFile('avatar')) {
            return [
                'avatar' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,jpg,png,gif',
                    'max:2048',
                ],
            ];
        }

        return [
            'avatar' => ['nullable'],
        ];
    }

    protected function passwordRules(bool $required = true): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'string',
            'min:8',
            'confirmed',
        ];
                
        return [
            'password' => $rules,
        ];
    }

    protected function rolesRules(): array
    {
        $guardName = config('permission.defaults.guard', 'web');
        
        return [
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', $guardName),
            ],
        ];
    }

    public function attributes(): array
    {
        $attrs = [
            'name' => __('user.fields.name'),
            'surname' => __('user.fields.surname'),
            'email' => __('user.fields.email'),
            'phone' => __('user.fields.phone'),
            'avatar' => __('user.fields.avatar'),
            'password' => __('user.fields.password'),
            'facebook_url' => __('user.fields.facebook_url'),
            'linkedin_url' => __('user.fields.linkedin_url'),
            'twitter_url' => __('user.fields.twitter_url'),
            'bio' => __('user.fields.bio'),
            'roles' => __('user.fields.roles'),
            'roles.*' => __('user.fields.role'),
        ];
        $localeCodes = \App\Models\Settings\Locale::active()->pluck('code')->toArray();
        foreach ($localeCodes as $code) {
            $attrs['bio_' . $code] = __('user.fields.bio');
            $attrs['name_' . $code] = __('user.fields.name');
            $attrs['surname_' . $code] = __('user.fields.surname');
        }
        return $attrs;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('user.validation.name_required'),
            'name.string' => __('user.validation.name_string'),
            'name.max' => __('user.validation.name_max'),

            'email.required' => __('user.validation.email_required'),
            'email.email' => __('user.validation.email_email'),
            'email.regex' => __('user.validation.email_regex'),
            'email.max' => __('user.validation.email_max'),
            'email.unique' => __('user.validation.email_unique'),

            'phone.required' => __('user.validation.phone_required'),
            'phone.regex' => __('user.validation.phone_regex'),
            'phone.max' => __('user.validation.phone_max'),
            'phone.format' => __('user.validation.phone_format'),

            'password.required' => __('user.validation.password_required'),
            'password.min' => __('user.validation.password_min'),
            'password.confirmed' => __('user.validation.password_confirmed'),

            'roles.array' => __('user.validation.roles_array'),
            'roles.*.required' => __('user.validation.role_required'),
            'roles.*.integer' => __('user.validation.role_integer'),
            'roles.*.exists' => __('user.validation.role_exists'),
        ];
    }
}
