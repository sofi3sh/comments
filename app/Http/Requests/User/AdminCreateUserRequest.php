<?php

namespace App\Http\Requests\User;

use App\Support\Permissions\CrudOperation;

class AdminCreateUserRequest extends BaseUserRequest
{
    public function rules(): array
    {
        $rules = array_merge(
            $this->nameRules(),
            $this->emailRules(),
            $this->phoneRules(required: true),
            $this->passwordRules(required: true),
            $this->avatarRules(),
        );

        if (has_crud_permission('user', CrudOperation::UPDATE_ROLES)) {
            $rules = array_merge($rules, $this->rolesRules());
        }

        return $rules;
    }
}
