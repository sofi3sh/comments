<?php

namespace App\Http\Requests\User;

class UserRegisterRequest extends BaseUserRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            $this->nameRules(),
            $this->emailRules(),
            $this->phoneRules(required: false),
            $this->passwordRules(required: true),
        );
    }
}