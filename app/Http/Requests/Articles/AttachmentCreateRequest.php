<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;

class AttachmentCreateRequest extends FormRequest
{
    public function rules(): array
    {
        $attachmentImageConfig = config('attachments.image');

        return [
            'file' => [
                'required',
                'file',
                'image',
                'mimetypes:' . implode(',', $attachmentImageConfig['mimetypes']),
                'extensions:' . implode(',', $attachmentImageConfig['extensions']),
                'max:10240',
                'dimensions:max_width=6000,max_height=6000',
                'max:' . $attachmentImageConfig['max_size_kb'],
                sprintf(
                    'dimensions:max_width=%d,max_height=%d',
                    $attachmentImageConfig['max_width'],
                    $attachmentImageConfig['max_height']
                ),
            ],
            'alt'          => 'nullable|string|max:255',
            'title'        => 'nullable|string|max:255',
            'caption'      => 'nullable|string|max:255',
            'article_tags' => 'nullable|array',
            'tag_ids'      => 'nullable|array',
            'tag_ids.*'    => 'integer|exists:tags,id',
        ];
    }


    protected function imageExtensions(): string
    {
        return strtoupper(
            implode(', ', config('attachments.image.extensions'))
        );
    }

    protected function maxFileSizeMb(): int
    {
        return (int) config('attachments.image.max_size_kb') / 1024;
    }
}
