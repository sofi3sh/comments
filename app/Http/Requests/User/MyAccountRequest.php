<?php

namespace App\Http\Requests\User;

use App\Models\Articles\ArticleMeta;
use Illuminate\Validation\Rule;

class MyAccountRequest extends BaseUserRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $user = backpack_user();
        $userId = $user ? $user->id : null;

        $isBloggerApp = session('dashboard_role_chosen') === 'blogger_application';
        $isCompanyApp = session('dashboard_role_chosen') === 'company_application';
        $isApplication = $isBloggerApp || $isCompanyApp;
        $avatarRequired = $isApplication && empty($user?->avatar);

        $companyTypeRule = Rule::in(ArticleMeta::companyTypeSlugs());

        $rules = array_merge(
            $this->nameRules(),
            $this->surnameRules(),
            $this->emailRules($userId),
            $this->phoneRules(required: $isApplication),
            $this->avatarRules(required: $avatarRequired),
            $this->avatarRules(required: $isApplication),
            $this->socialUrlRules($isApplication),
            $this->bioTranslationRules(required: $isApplication),
            $this->nameTranslationRules(),
            $this->surnameTranslationRules(),
        );

        $rules['company_id'] = ['nullable', 'integer'];
        $rules['company_title'] = ['nullable', 'string', 'max:255'];
        $rules['company_edrpou'] = ['nullable', 'string', 'max:255'];
        $rules['company_website'] = ['nullable', 'string', 'max:255'];
        $rules['company_social'] = ['nullable', 'string', 'max:255'];
        $rules['company_phone'] = ['nullable', 'string', 'max:255'];
        $rules['company_director'] = ['nullable', 'string', 'max:255'];
        $rules['company_logo'] = ['nullable', 'image', 'max:2048'];
        $rules['company_position'] = ['nullable', 'string', 'max:255'];
        $rules['company_type'] = ['nullable', 'string', $companyTypeRule];

        if ($isCompanyApp) {
            $hasExistingCompany = (bool) $this->input('company_id');

            if ($hasExistingCompany) {

                $rules['company_id'] = ['required', 'integer'];
                $rules['company_title'] = ['nullable', 'string', 'max:255'];
                $rules['company_edrpou'] = ['nullable', 'string', 'max:255'];
                $rules['company_website'] = ['nullable', 'string', 'max:255'];
                $rules['company_social'] = ['nullable', 'string', 'max:255'];
                $rules['company_phone'] = ['nullable', 'string', 'max:255'];
                $rules['company_director'] = ['nullable', 'string', 'max:255'];
                $rules['company_logo'] = ['nullable', 'image', 'max:2048'];
                $rules['company_position'] = ['nullable', 'string', 'max:255'];
                $rules['company_type'] = ['nullable', 'string', $companyTypeRule];
            } else {
                $rules['company_title'] = ['required', 'string', 'max:255'];
                $rules['company_edrpou'] = ['required', 'string', 'max:255'];
                $rules['company_website'] = ['required', 'string', 'max:255'];
                $rules['company_social'] = ['required', 'string', 'max:255'];
                $rules['company_phone'] = ['required', 'string', 'max:255'];
                $rules['company_director'] = ['required', 'string', 'max:255'];
                $rules['company_logo'] = ['required', 'image', 'max:2048'];
                $rules['company_position'] = ['required', 'string', 'max:255'];
                $rules['company_type'] = ['required', 'string', $companyTypeRule];
            }
        }

        return $rules;
    }
}
