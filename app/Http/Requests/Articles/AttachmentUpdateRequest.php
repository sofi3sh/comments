<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;

class AttachmentUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'alt'          => 'nullable|string|max:255',
            'title'        => 'nullable|string|max:255',
            'caption'      => 'nullable|string|max:255',
            'article_tags' => 'nullable|array',
            'tag_ids'      => 'nullable|array',
            'tag_ids.*'    => 'integer|exists:tags,id',
        ];
    }
}