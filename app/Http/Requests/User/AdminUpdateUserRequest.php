<?php

namespace App\Http\Requests\User;

use App\Support\Permissions\CrudOperation;

class AdminUpdateUserRequest extends BaseUserRequest
{
    public function rules(): array
    {
        $routeUser = $this->route('user') ?? $this->route('id');
        $userId = is_object($routeUser) && method_exists($routeUser, 'getKey')
            ? $routeUser->getKey()
            : $routeUser;

        $rules = array_merge(
            $this->nameRules(),
            $this->emailRules(ignoreUserId: $userId),
            $this->phoneRules(required: false),
            $this->passwordRules(required: false),
            $this->avatarRules(),
        );

        if (has_crud_permission('user', CrudOperation::UPDATE_ROLES)) {
            $rules = array_merge($rules, $this->rolesRules());
        }

        return $rules;
    }
}
