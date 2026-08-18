<?php

namespace App\Http\Requests\Articles;

use App\Models\Settings\Locale;
use App\Support\Validation\SeoValidation;

class TagRequest extends TranslatableCrudRequest
{
    protected array $translatedFields = [
        'title',
        'slug',
        'main_title',
        'heading',
    ];

    public function authorize(): bool
    {
        return backpack_auth()->check();
    }


    protected function prepareForValidation(): void
    {
        $data = $this->all();

        $this->generateTranslationSlugs(
            data: $data,
            field: 'title',
            table: 'tag_translations',
            ownerColumn: 'tag_id',
            ownerId: $this->route('id'),
        );

        $this->replace($data);
    }


    public function rules(): array
    {
        $rules = [

            'homepage' => [
                'bool'
            ],

            'translations' => [
                'required',
                'array',
                'min:1',
            ],

            'translations.*.title' => [
                'required',
                'string',
                'max:255',
            ],

            'translations.*.main_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'translations.*.heading' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];


        return array_merge($rules,SeoValidation::rules());
    }


    public function attributes(): array
    {
        $attributes = [];

        $locales = Locale::getAvailableAsArr();

        foreach ($locales as $locale) {

            foreach ($this->translatedFields as $field) {

                $attributes["translations.$locale.$field"] =
                    __("tag.fields.$field") . " ($locale)";
            }
        }

        return $attributes;
    }
}

