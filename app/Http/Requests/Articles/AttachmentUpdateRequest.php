<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;

class AttachmentUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_public' => $this->boolean('is_public'),
        ]);
    }

    public function rules(): array
    {
        return [
            'alt'          => 'nullable|string|max:255',
            'title'        => 'nullable|string|max:255',
            'caption'      => 'nullable|string|max:255',
            'article_tags' => 'nullable|array',
            'tag_ids'      => 'nullable|array',
            'tag_ids.*'    => 'integer|exists:tags,id',
            'is_public'    => 'boolean',
        ];
    }
}
